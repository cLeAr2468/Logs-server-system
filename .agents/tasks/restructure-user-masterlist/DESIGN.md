# Technical Design: User-Masterlist Database Restructuring

## Overview

This design addresses the elimination of data redundancy between the `masterlist` and `users` tables by establishing a proper relational database structure. Currently, the `users` table duplicates student information (name, email, course, year_level) that already exists in the `masterlist` table. The restructured system will use a foreign key relationship where `users` references `masterlist`, making the masterlist the single source of truth for student identity and academic information while allowing users to maintain their own address data and authentication credentials.

The restructuring introduces encrypted storage for sensitive personal information to meet privacy and security requirements. Address fields will be added to the `users` table with encryption, and personal identification fields in `masterlist` will also be encrypted using Laravel's encrypted casting feature.

## Database Schema Changes

### Migration Strategy

**Migration File**: `2026_10_01_000000_restructure_user_masterlist_relationship.php`

The migration will execute in this sequence to preserve existing data:

1. **Add new columns to users table** (all nullable during transition):
   - `masterlist_id` (unsignedBigInteger, nullable, indexed)
   - `region` (text, nullable, encrypted)
   - `province` (text, nullable, encrypted) 
   - `municipality` (text, nullable, encrypted)
   - `barangay` (text, nullable, encrypted)
   - `suffix` (string, nullable)

2. **Populate masterlist_id for existing users**:
   - Match users to masterlist entries using `student_id`
   - For each user where a matching masterlist entry exists, set `users.masterlist_id = masterlist.id`
   - Log any users that cannot be matched to masterlist

3. **Drop redundant columns from users table**:
   - `fname`, `mname`, `lname`, `email`, `course`, `year_level`
   - These will now be accessed via the relationship

4. **Add foreign key constraint**:
   - Add foreign key: `users.masterlist_id` references `masterlist.id` with `onDelete('cascade')`
   - This ensures referential integrity going forward

5. **Add encrypted casting to masterlist**:
   - Encrypt existing data in `fname`, `mname`, `lname`, `email` columns
   - This is handled via model casting, not migration

**Transition Period Handling**: The `masterlist_id` column remains nullable during the migration to handle edge cases where users might exist without masterlist entries. After data migration completes, a validation step will identify orphaned users, allowing manual resolution before making the foreign key non-nullable in a follow-up migration if needed.

### Rollback Strategy

The migration `down()` method will:

1. Drop foreign key constraint on `masterlist_id`
2. Re-add columns: `fname`, `mname`, `lname`, `email`, `course`, `year_level` to users table
3. Repopulate these columns from the related masterlist entries
4. Drop the new columns: `masterlist_id`, `region`, `province`, `municipality`, `barangay`, `suffix`

**Critical Consideration**: Rollback will not preserve the encrypted address data in plain text. The rollback is designed for immediate migration failure recovery, not for production rollback after deployment. Once encrypted data is in production, rollback should be considered destructive.

## Model Relationship Configuration

### User Model Changes

**File**: `app/Models/User.php`

**Relationship Definition**:
```php
public function masterlist()
{
    return $this->belongsTo(Masterlist::class, 'masterlist_id');
}
```

**Updated $fillable array**:
```php
protected $fillable = [
    'student_id',      // Keep for queries and activity logs
    'masterlist_id',   // Foreign key
    'region',
    'province',
    'municipality',
    'barangay',
    'suffix',
    'address',         // Existing full address text field
    'status',
    'profile',
    'password',
];
```

**Encrypted Casting**:
```php
protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'region' => 'encrypted',
    'province' => 'encrypted',
    'municipality' => 'encrypted',
    'barangay' => 'encrypted',
];
```

**Accessor Methods** (for backward compatibility with existing code):
```php
public function getFnameAttribute()
{
    return $this->masterlist?->fname;
}

public function getMnameAttribute()
{
    return $this->masterlist?->mname;
}

public function getLnameAttribute()
{
    return $this->masterlist?->lname;
}

public function getEmailAttribute()
{
    return $this->masterlist?->email;
}

public function getCourseAttribute()
{
    return $this->masterlist?->course;
}

public function getYearLevelAttribute()
{
    return $this->masterlist?->year_level;
}
```

These accessors allow existing code that references `$user->fname` to continue working without immediate refactoring. However, code should be updated to use `$user->masterlist->fname` for clarity.

### Masterlist Model Changes

**File**: `app/Models/Masterlist.php`

**Relationship Definition**:
```php
public function user()
{
    return $this->hasOne(User::class, 'masterlist_id');
}
```

**Encrypted Casting**:
```php
protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'fname' => 'encrypted',
    'mname' => 'encrypted',
    'lname' => 'encrypted',
    'email' => 'encrypted',
];
```

**Critical Note on Encryption**: Once encryption is applied, searching and sorting by these fields directly in the database becomes impossible. Encrypted fields must be decrypted in application code for comparison. This impacts:
- Search functionality (must load all records and filter in PHP)
- Email uniqueness validation (requires full table scan)
- Sorting by name

For email uniqueness, we'll create a hashed email index column for validation while keeping the actual email encrypted.

### Additional Masterlist Schema Change

Add a hashed email column for uniqueness validation:

**Migration**: `2026_10_01_000001_add_email_hash_to_masterlist.php`

```php
Schema::table('masterlist', function (Blueprint $table) {
    $table->string('email_hash', 64)->after('email')->unique();
});
```

**Masterlist Model Event**:
```php
protected static function boot()
{
    parent::boot();
    
    static::saving(function ($masterlist) {
        if ($masterlist->isDirty('email')) {
            $masterlist->email_hash = hash('sha256', strtolower($masterlist->email));
        }
    });
}
```

This allows fast email uniqueness checks using `where('email_hash', hash('sha256', strtolower($email)))->exists()`.

## Encryption Implementation

### Encryption Strategy

Laravel's encrypted casting uses the application key defined in `APP_KEY` in the `.env` file. This provides AES-256-CBC encryption.

**Critical Requirements**:
1. `APP_KEY` must be set and never changed after encrypting data (changing it makes existing encrypted data unreadable)
2. Backup the `APP_KEY` securely
3. Encrypted columns should be defined as `text` type in migrations (not `string`) to accommodate encrypted data overhead

**Data Migration for Encryption**:

Create an artisan command to encrypt existing data:

**File**: `app/Console/Commands/EncryptExistingData.php`

This command will:
1. Load all masterlist entries
2. For each entry, decrypt (plain text initially) and re-save with encrypted casting enabled
3. Load all users with address data
4. Re-save to apply encrypted casting
5. Report any failures

The command runs after the migration and before deployment.

### Performance Considerations

Encrypted fields have performance implications:
- **Storage overhead**: Encrypted data is ~1.3x larger than plain text
- **CPU overhead**: Encryption/decryption on every read/write
- **Query limitations**: Cannot use WHERE, ORDER BY, or index scans on encrypted values

To mitigate:
- Use eager loading (`with('masterlist')`) to minimize queries
- Cache frequently accessed data (user profiles)
- Use `email_hash` for lookups instead of encrypted email

## Backend API Modifications

### AuthController Changes

**File**: `app/Http/Controllers/AuthController.php`

#### 1. getStudentFromMasterlist()

**Current behavior**: Returns student data directly from masterlist

**Changes**:
- Return decrypted data (Laravel handles automatically via model casting)
- No logic changes needed; encryption is transparent

**Updated response**:
```php
return response()->json([
    'student' => [
        'student_id' => $student->student_id,
        'fname' => $student->fname,           // Auto-decrypted
        'mname' => $student->mname,           // Auto-decrypted
        'lname' => $student->lname,           // Auto-decrypted
        'email' => $student->email,           // Auto-decrypted
        'course' => $student->course,
        'year_level' => $student->year_level,
    ]
], 200);
```

#### 2. register()

**Current behavior**: Stores duplicate data in users table

**Changes**:

1. **Validation changes**:
   - Remove validation for: `fname`, `mname`, `lname`, `email`, `course`, `year_level`
   - Add validation for: `region` (required, string), `suffix` (nullable, string, in:Jr.,Sr.,II,III,IV)
   - Keep validation for: `student_id`, `barangay`, `municipality`, `province`, `password`

2. **Masterlist lookup**:
   - Find masterlist entry by `student_id`
   - Check if masterlist entry already has an associated user: `if ($masterlistEntry->user)` then reject with "Student ID is already registered"

3. **User creation**:
```php
$user = User::create([
    'student_id' => $request->student_id,     // Keep for indexing
    'masterlist_id' => $masterlistEntry->id,  // Foreign key
    'region' => $request->region,             // Auto-encrypted
    'province' => $request->province,         // Auto-encrypted
    'municipality' => $request->municipality, // Auto-encrypted
    'barangay' => $request->barangay,         // Auto-encrypted
    'suffix' => $request->suffix,
    'status' => 'Active',
    'password' => Hash::make($request->password),
]);
```

4. **Response changes**:
   - Return user data with masterlist relationship loaded:
```php
return response()->json([
    'message' => 'User registered successfully',
    'user' => [
        'id' => $user->id,
        'student_id' => $user->student_id,
        'fname' => $user->masterlist->fname,
        'mname' => $user->masterlist->mname,
        'lname' => $user->masterlist->lname,
        'email' => $user->masterlist->email,
        'course' => $user->masterlist->course,
        'year_level' => $user->masterlist->year_level,
        'region' => $user->region,                    // Auto-decrypted for response
        'province' => $user->province,
        'municipality' => $user->municipality,
        'barangay' => $user->barangay,
        'suffix' => $user->suffix,
        'status' => $user->status,
    ]
], 201);
```

5. **Email uniqueness check**:
   - Replace direct email checks with hash-based lookup:
```php
$emailHash = hash('sha256', strtolower($request->email));
if (Masterlist::where('email_hash', $emailHash)->exists()) {
    return response()->json(['message' => 'Email already exists'], 422);
}
```

#### 3. login()

**Current behavior**: Queries user by email directly

**Changes**:

Login flow is more complex because the email is now in the encrypted masterlist table.

**Approach**: Add an `email_hash` column to the `users` table that mirrors the masterlist email hash, updated when the user is created or when the masterlist email changes.

**Alternative Approach** (simpler, chosen for this design): 
- Find masterlist entry by email hash
- Get associated user via relationship
- Verify password

```php
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    // Find masterlist by email hash
    $emailHash = hash('sha256', strtolower($request->email));
    $masterlist = Masterlist::where('email_hash', $emailHash)->first();

    if (!$masterlist || !$masterlist->user) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = $masterlist->user;

    if (!Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Rest of login logic remains the same
    $token = $user->createToken('auth_token')->plainTextToken;

    // Log activity...

    return response()->json([
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'student_id' => $user->student_id,
            'fname' => $user->masterlist->fname,
            'mname' => $user->masterlist->mname,
            'lname' => $user->masterlist->lname,
            'full_name' => $user->full_name,
            'email' => $user->masterlist->email,
            'region' => $user->region,
            'province' => $user->province,
            'municipality' => $user->municipality,
            'barangay' => $user->barangay,
            'suffix' => $user->suffix,
            'course' => $user->masterlist->course,
            'year_level' => $user->masterlist->year_level,
            'status' => $user->status,
            'address' => $user->address,
        ]
    ]);
}
```

**Error Handling**: If the masterlist entry exists but has no associated user, this is treated as invalid credentials (user has not registered yet).

#### 4. getProfile()

**Changes**:
- Eager load masterlist relationship
- Return masterlist fields separately from user fields

```php
public function getProfile(Request $request)
{
    $user = $request->user()->load('masterlist');

    return response()->json([
        'user' => [
            'id' => $user->id,
            'student_id' => $user->student_id,
            'firstname' => $user->masterlist->fname,
            'middlename' => $user->masterlist->mname,
            'lastname' => $user->masterlist->lname,
            'email' => $user->masterlist->email,
            'course' => $user->masterlist->course,
            'year' => $user->masterlist->year_level,
            'region' => $user->region,
            'province' => $user->province,
            'municipality' => $user->municipality,
            'barangay' => $user->barangay,
            'suffix' => $user->suffix,
            'address' => $user->address,
            'status' => $user->status,
            'profile' => $user->profile,
        ]
    ], 200);
}
```

#### 5. updateProfile()

**Current behavior**: Allows updating name, email, course, year_level

**New behavior**: Only address fields are editable by users; identity fields are read-only

**Changes**:

1. **Validation changes**:
   - Remove: `student_id`, `firstname`, `middlename`, `lastname`, `email`, `course`, `year`
   - Keep/Update: `region`, `province`, `municipality`, `barangay`, `suffix`, `address`, `status`

2. **Update logic**:
```php
public function updateProfile(Request $request)
{
    $user = $request->user();

    $request->validate([
        'region' => 'nullable|string',
        'province' => 'nullable|string|max:255',
        'municipality' => 'nullable|string|max:255',
        'barangay' => 'nullable|string|max:255',
        'suffix' => 'nullable|string|in:Jr.,Sr.,II,III,IV',
        'address' => 'nullable',
        'status' => 'nullable',
    ]);

    // Only update address-related fields
    $user->update($request->only([
        'region',
        'province',
        'municipality',
        'barangay',
        'suffix',
        'address',
        'status'
    ]));

    return response()->json([
        'message' => 'Profile updated successfully',
        'user' => [
            'id' => $user->id,
            'student_id' => $user->student_id,
            'firstname' => $user->masterlist->fname,
            'middlename' => $user->masterlist->mname,
            'lastname' => $user->masterlist->lname,
            'email' => $user->masterlist->email,
            'course' => $user->masterlist->course,
            'year' => $user->masterlist->year_level,
            'region' => $user->region,
            'province' => $user->province,
            'municipality' => $user->municipality,
            'barangay' => $user->barangay,
            'suffix' => $user->suffix,
            'address' => $user->address,
            'status' => $user->status,
            'profile' => $user->profile,
        ]
    ], 200);
}
```

**Note**: If users need to update their name, email, course, or year_level, they must contact an administrator who will update the masterlist entry. This change propagates automatically to all user responses via the relationship.

#### 6. update() and destroy()

These methods remain largely unchanged but should eager load the masterlist relationship when returning user data in responses.

**Error Handling Update**: Add validation to ensure masterlist relationship exists before performing operations.

### UserController Changes

**File**: `app/Http/Controllers/UserController.php`

#### 1. index()

**Changes**:
- Eager load masterlist relationship: `User::with('masterlist')->orderBy('created_at', 'desc')->get()`
- Response structure remains the same (accessors handle field retrieval)

```php
public function index()
{
    try {
        $users = User::with('masterlist')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'users' => $users,
            'total' => $users->count(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch users',
            'error' => $e->getMessage(),
        ], 500);
    }
}
```

#### 2. store()

**Changes**: Same as `AuthController::register()` but called by admin/staff:

1. Remove validation for identity fields
2. Add validation for new address fields
3. Update user creation to use `masterlist_id` and new address fields
4. Check for existing user via `masterlist->user` relationship

#### 3. update()

**Changes**:
- Remove identity field updates
- Only allow updating `status` and address fields
- Identity updates must be done via MasterlistController

```php
public function update(Request $request, $id)
{
    try {
        $user = User::with('masterlist')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'region' => 'nullable|string',
            'province' => 'nullable|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|in:Jr.,Sr.,Sr,Jr,II,III,IV,V',
            'status' => 'sometimes|required|in:Active,Inactive',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = $request->only(['region', 'province', 'municipality', 'barangay', 'suffix', 'status']);

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully!',
            'user' => $user,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update user',
            'error' => $e->getMessage(),
        ], 500);
    }
}
```

#### 4. show(), destroy(), statistics()

- Add eager loading for masterlist relationship
- No other changes needed

### MasterlistController Changes

**File**: `app/Http/Controllers/MasterlistController.php`

The masterlist controller becomes more powerful as it controls the identity data for all users.

#### Key Changes:

1. **store()**: When creating a masterlist entry, check uniqueness using `email_hash`

2. **update()**: When updating masterlist entry:
   - Update the encrypted fields (automatic via casting)
   - Update `email_hash` if email changes
   - Send notification emails to the associated user if one exists
   - Changes propagate automatically to user data via relationship

3. **destroy()**: When deleting a masterlist entry:
   - Due to cascading foreign key, the associated user account will be deleted
   - Add a confirmation check: if masterlist entry has an associated user, require explicit confirmation
   - Send deletion notification to user's email before deletion

**Updated destroy() logic**:
```php
public function destroy(Request $request, $id)
{
    \DB::beginTransaction();
    
    try {
        $masterlist = Masterlist::with('user')->findOrFail($id);
        
        // Check if there's an associated user account
        if ($masterlist->user) {
            // Require explicit confirmation via request parameter
            if (!$request->has('confirm_user_deletion') || !$request->confirm_user_deletion) {
                return response()->json([
                    'success' => false,
                    'message' => 'This masterlist entry has an associated user account. Deleting it will also delete the user account. Please confirm.',
                    'requires_confirmation' => true,
                    'user_id' => $masterlist->user->student_id,
                ], 400);
            }
        }
        
        $studentEmail = $masterlist->email;
        $user = $request->user();
        $deletedBy = $user ? trim($user->fname . ' ' . $user->lname) : 'System Administrator';
        
        // Send deletion notification
        try {
            \Mail::to($studentEmail)->send(new \App\Mail\MasterlistDeletedNotification($masterlist, $deletedBy));
        } catch (\Exception $mailError) {
            \Log::error('Failed to send deletion email: ' . $mailError->getMessage());
        }
        
        // Delete masterlist (cascades to user)
        $masterlist->delete();
        
        // Log activity...
        
        \DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Masterlist entry deleted successfully!' . ($masterlist->user ? ' Associated user account was also deleted.' : ''),
        ], 200);
    } catch (\Exception $e) {
        \DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete masterlist entry',
            'error' => $e->getMessage(),
        ], 500);
    }
}
```

#### Email Hash Usage:

In all email validation checks, replace:
```php
Masterlist::where('email', $request->email)->exists()
```

With:
```php
$emailHash = hash('sha256', strtolower($request->email));
Masterlist::where('email_hash', $emailHash)->exists()
```

## Frontend Updates

### Register Form Changes

**File**: `c:\Users\User\Desktop\Client-Module\logs-system\src\components\pages\register.jsx`

#### 1. Remove editable identity fields

The following fields should be **read-only** (fetched from masterlist):
- First Name
- Middle Name
- Last Name
- Email
- Course
- Year Level

These are already read-only for email in the current implementation. Update the others:

```jsx
<Input
  id="fname"
  placeholder="First Name"
  className="h-9 border bg-gray-100 text-sm cursor-not-allowed"
  value={form.fname}
  readOnly
/>
```

#### 2. Add Region Dropdown

**Philippine Regions** (17 regions):
1. NCR - National Capital Region
2. CAR - Cordillera Administrative Region
3. Region I - Ilocos Region
4. Region II - Cagayan Valley
5. Region III - Central Luzon
6. Region IV-A - CALABARZON
7. MIMAROPA
8. Region V - Bicol Region
9. Region VI - Western Visayas
10. Region VII - Central Visayas
11. Region VIII - Eastern Visayas
12. Region IX - Zamboanga Peninsula
13. Region X - Northern Mindanao
14. Region XI - Davao Region
15. Region XII - SOCCSKSARGEN
16. Region XIII - Caraga
17. BARMM - Bangsamoro Autonomous Region in Muslim Mindanao

**Implementation**:

```jsx
const philippineRegions = [
  'NCR - National Capital Region',
  'CAR - Cordillera Administrative Region',
  'Region I - Ilocos Region',
  'Region II - Cagayan Valley',
  'Region III - Central Luzon',
  'Region IV-A - CALABARZON',
  'MIMAROPA',
  'Region V - Bicol Region',
  'Region VI - Western Visayas',
  'Region VII - Central Visayas',
  'Region VIII - Eastern Visayas',
  'Region IX - Zamboanga Peninsula',
  'Region X - Northern Mindanao',
  'Region XI - Davao Region',
  'Region XII - SOCCSKSARGEN',
  'Region XIII - Caraga',
  'BARMM - Bangsamoro Autonomous Region in Muslim Mindanao'
];

// Add to form component:
<div>
  <label className="mb-1.5 block text-sm font-medium">
    Region: <span className="text-red-500">*</span>
  </label>
  <Select 
    value={form.region}
    onValueChange={(value) => setForm({ ...form, region: value })}
    required
  >
    <SelectTrigger className="h-9 w-full border border-gray-300 bg-white text-sm">
      <SelectValue placeholder="Select Region" />
    </SelectTrigger>
    <SelectContent className="text-sm">
      {philippineRegions.map((region) => (
        <SelectItem key={region} value={region}>
          {region}
        </SelectItem>
      ))}
    </SelectContent>
  </Select>
  {touched.region && errors.region && (
    <p className="text-xs text-red-500 mt-1 flex items-center gap-1">
      <XCircle size={12} />
      {errors.region}
    </p>
  )}
</div>
```

**Placement**: Add the Region dropdown before the Province/Municipality/Barangay AddressSelector component.

#### 3. Add Suffix Dropdown

**Suffix options**: Jr., Sr., II, III, IV, V (optional field)

```jsx
const suffixOptions = ['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'];

// Add to form component (after Last Name field):
<div>
  <label className="mb-1.5 block text-sm font-medium">
    Suffix (Optional):
  </label>
  <Select 
    value={form.suffix}
    onValueChange={(value) => setForm({ ...form, suffix: value })}
  >
    <SelectTrigger className="h-9 w-full border border-gray-300 bg-white text-sm">
      <SelectValue placeholder="Select Suffix (if applicable)" />
    </SelectTrigger>
    <SelectContent className="text-sm">
      {suffixOptions.map((suffix) => (
        <SelectItem key={suffix} value={suffix}>
          {suffix}
        </SelectItem>
      ))}
    </SelectContent>
  </Select>
</div>
```

#### 4. Update form state initialization

```jsx
const [form, setForm] = useState({
  student_id: "",
  fname: "",           // Read-only, from masterlist
  mname: "",           // Read-only, from masterlist
  lname: "",           // Read-only, from masterlist
  email: "",           // Read-only, from masterlist
  course: "",          // Read-only, from masterlist
  year_level: "",      // Read-only, from masterlist
  region: "",          // New, required
  province: "",        // Existing, encrypted
  municipality: "",    // Existing, encrypted
  barangay: "",        // Existing, encrypted
  suffix: "",          // New, optional
  password: "",
});
```

#### 5. Update handleStudentIdBlur

The existing implementation already fetches from masterlist. No changes needed, but ensure it sets all fields to read-only.

#### 6. Update form submission

The form submission should send:
- `student_id` (for lookup)
- `region`, `province`, `municipality`, `barangay`, `suffix` (address data)
- `password`

Remove from submission: `fname`, `mname`, `lname`, `email`, `course`, `year_level`

```jsx
const handleSubmit = async (e) => {
  e.preventDefault();

  if (!validateAllFields()) {
    toast.error("Please fix all validation errors before submitting");
    return;
  }

  if (form.password !== confirmPassword) {
    toast.error("Passwords do not match!");
    return;
  }

  setLoading(true);

  try {
    const registrationData = {
      student_id: form.student_id,
      region: form.region,
      province: form.province,
      municipality: form.municipality,
      barangay: form.barangay,
      suffix: form.suffix,
      password: form.password,
    };

    const response = await api.post("/register", registrationData);

    toast.success("Registered successfully! Redirecting to login...");

    // Reset form...
    setTimeout(() => {
      window.location.href = "/";
    }, 1500);
  } catch (error) {
    toast.error(error.response?.data?.message || "Registration failed. Please try again.");
  } finally {
    setLoading(false);
  }
};
```

### Profile/Update Form Changes

If there are profile editing components, they should:
1. Display name, email, course, year_level as **read-only** with a note: "To update these fields, please contact the administrator"
2. Allow editing of: region, province, municipality, barangay, suffix, password

## Error Handling

### Database Relationship Errors

**Scenario**: User attempts to log in but their masterlist entry was deleted.

**Handling**: 
- In `login()`, check if masterlist relationship is null
- Return: "Your account has been deactivated. Please contact the administrator."
- Log this event for investigation

**Scenario**: Admin attempts to delete a masterlist entry with an associated user without confirmation.

**Handling**:
- Return HTTP 400 with `requires_confirmation: true` and user information
- Frontend shows confirmation dialog explaining the consequences

### Encryption Errors

**Scenario**: Encrypted data cannot be decrypted (APP_KEY mismatch or corruption).

**Handling**:
- Catch `Illuminate\Contracts\Encryption\DecryptException`
- Log the error with the record ID
- Return generic error to user: "Unable to load profile data. Please contact support."
- Provide admin tools to identify and re-encrypt corrupted records

**Implementation** (add to User model):
```php
public function getFullNameAttribute()
{
    try {
        return trim("{$this->masterlist->fname} {$this->masterlist->mname} {$this->masterlist->lname}");
    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
        \Log::error("Decryption failed for user {$this->id}: " . $e->getMessage());
        return "Error loading name";
    }
}
```

### Migration Edge Cases

**Orphaned Users**: Users exist without matching masterlist entries.

**Handling**:
- During migration, log all users that cannot be matched to masterlist
- Create artisan command `php artisan users:audit-masterlist-orphans` to identify these
- Admin must either:
  1. Create a masterlist entry for the student
  2. Delete the orphaned user account

**Duplicate Student IDs**: Multiple masterlist entries or users with same student_id.

**Handling**:
- Pre-migration validation command: `php artisan masterlist:validate-uniqueness`
- Reports duplicates
- Migration fails if duplicates exist
- Admin must resolve duplicates manually before proceeding

## Validation Layer Enforcement

### Masterlist Invariants

**Enforced at**: Masterlist model and controller

1. **Email uniqueness**: Enforced via `email_hash` unique constraint and validation in controller
2. **Student ID uniqueness**: Existing unique constraint on `student_id` column
3. **Required fields**: fname, lname, email, course, year_level, student_id (database not null + validation)

**Enforcement Strategy**: Database constraints (primary) + validation layer (secondary) + model events (tertiary for computed fields like email_hash).

### User Invariants

**Enforced at**: User model and controller

1. **Masterlist relationship required**: After transition, every user must have a valid masterlist_id
2. **Address completeness**: If any address field is provided, region must be provided (enforced in validation)
3. **Password complexity**: Enforced in validation (frontend and backend)

**Enforcement Strategy**: Foreign key constraint (database) + validation layer (application).

## Testing Strategy

### Unit Tests

**Location**: `tests/Unit/`

1. **ModelRelationshipTest.php**:
   - Test `User::masterlist()` returns Masterlist instance
   - Test `Masterlist::user()` returns User instance or null
   - Test accessor methods (`$user->fname`) return correct values
   - Test cascading delete (deleting masterlist deletes user)

2. **EncryptionTest.php**:
   - Test encrypted fields are stored encrypted in database (raw query check)
   - Test decryption returns original value
   - Test searching by email_hash works correctly
   - Test email_hash is updated when email changes

3. **ValidationTest.php**:
   - Test user creation fails without valid masterlist_id
   - Test duplicate registration is prevented
   - Test address field validation rules

### Integration Tests

**Location**: `tests/Feature/`

1. **AuthTest.php**:
   - Test registration with valid masterlist entry
   - Test registration fails with non-existent student_id
   - Test registration fails with already-registered student_id
   - Test login with email and password
   - Test login fails when masterlist entry is deleted
   - Test getProfile returns correct data structure
   - Test updateProfile only updates address fields

2. **UserControllerTest.php**:
   - Test admin can create user with valid masterlist_id
   - Test admin cannot update user identity fields (only address/status)
   - Test listing users includes masterlist data
   - Test deleting user doesn't delete masterlist

3. **MasterlistControllerTest.php**:
   - Test creating masterlist entry encrypts data
   - Test updating masterlist propagates to user responses
   - Test deleting masterlist with user requires confirmation
   - Test deleting masterlist without user works immediately
   - Test email uniqueness via hash

### Manual Testing Checklist

1. **Pre-Migration**:
   - [ ] Run validation commands to check for orphaned users and duplicates
   - [ ] Backup database
   - [ ] Verify APP_KEY is set and backed up

2. **Migration**:
   - [ ] Run migration on staging environment
   - [ ] Verify all users have masterlist_id populated
   - [ ] Check migration logs for any failures
   - [ ] Verify old columns are dropped
   - [ ] Test rollback on separate database

3. **Post-Migration**:
   - [ ] Run encryption command
   - [ ] Verify encrypted data is not readable in database
   - [ ] Test login for multiple users
   - [ ] Test registration flow end-to-end
   - [ ] Test profile updates (verify identity fields are read-only)
   - [ ] Test admin masterlist updates propagate to users
   - [ ] Test masterlist deletion with associated user

4. **Frontend**:
   - [ ] Verify region dropdown shows all 17 Philippine regions
   - [ ] Verify suffix dropdown shows all options
   - [ ] Verify identity fields are read-only after masterlist fetch
   - [ ] Test form validation for new required fields
   - [ ] Test registration submission with encrypted fields

## Performance Optimization

### N+1 Query Prevention

Always use eager loading when fetching users:

```php
// Good
$users = User::with('masterlist')->get();

// Bad
$users = User::all();
// Later: $users->each(fn($u) => $u->masterlist->fname); // N+1!
```

**Where to apply**:
- `UserController::index()`
- `AuthController::getProfile()`
- Any user listing or profile endpoints

### Caching Strategy

Encrypted decryption is CPU-intensive. Cache frequently accessed data:

```php
public function getProfile(Request $request)
{
    $user = $request->user();
    
    $cacheKey = "user_profile_{$user->id}";
    
    $profile = Cache::remember($cacheKey, 3600, function() use ($user) {
        $user->load('masterlist');
        return [
            'id' => $user->id,
            'student_id' => $user->student_id,
            'firstname' => $user->masterlist->fname,
            // ... rest of profile
        ];
    });
    
    return response()->json(['user' => $profile], 200);
}
```

**Cache Invalidation**: Invalidate cache when:
- User updates profile
- Admin updates masterlist entry for that user

### Database Indexing

Ensure these indexes exist:
- `users.masterlist_id` (foreign key creates index automatically)
- `users.student_id` (existing unique creates index)
- `masterlist.email_hash` (unique constraint creates index)
- `masterlist.student_id` (existing unique creates index)

## Security Considerations

### Encryption Key Management

**Critical**:
1. Never commit `APP_KEY` to version control
2. Use different keys for development, staging, and production
3. Store production key in secure vault (AWS Secrets Manager, Azure Key Vault, etc.)
4. Implement key rotation strategy (requires re-encrypting all data)
5. Backup encryption key alongside database backups

### Access Control

**Identity Data Updates**:
- Only admin/staff can update masterlist entries
- Users cannot update their own identity data
- Log all masterlist changes to activity log
- Implement approval workflow for sensitive changes (optional future enhancement)

**Address Data**:
- Users can update their own address fields
- Admin/staff can update any user's address fields
- Log profile changes to activity log

### Input Validation

All user inputs must be validated and sanitized:
- Region: Must be one of the 17 Philippine regions
- Suffix: Must be one of the allowed values
- Province, Municipality, Barangay: Max length, no script injection
- Student ID: Format validation (existing: `21-SJ-0001`)

### Encrypted Data Exposure

**Risk**: Accidentally logging or exposing decrypted sensitive data.

**Mitigation**:
1. Never log decrypted name or email fields
2. Use `student_id` in logs instead of name
3. Mask email in error messages (show `j***@example.com` instead of full email)
4. Review all `dd()`, `var_dump()`, and `Log::` statements before deployment

## Deployment Plan

### Phase 1: Preparation (Pre-deployment)

1. **Code Review**: Review all modified files for security and correctness
2. **Testing**: Run full test suite (unit, integration, manual)
3. **Backup**: Create full database backup with encryption key backup
4. **Validation**: Run pre-migration validation commands to identify issues

### Phase 2: Deployment (Maintenance Window)

1. **Enable Maintenance Mode**: `php artisan down`
2. **Pull Latest Code**: Deploy restructured codebase to server
3. **Run Migration**: `php artisan migrate`
4. **Run Encryption Command**: `php artisan data:encrypt-existing`
5. **Clear Caches**: `php artisan cache:clear && php artisan config:clear`
6. **Verify**: Run smoke tests on staging-like environment
7. **Disable Maintenance Mode**: `php artisan up`

### Phase 3: Post-Deployment Monitoring

1. **Monitor Logs**: Watch for decryption errors or relationship errors
2. **User Feedback**: Collect feedback on registration and profile flows
3. **Performance**: Monitor query performance and encryption overhead
4. **Audit**: Run `php artisan users:audit-masterlist-orphans` to ensure no orphaned data

### Rollback Procedure

**If critical issues are discovered**:

1. Enable maintenance mode
2. Restore database backup
3. Deploy previous codebase version
4. Clear caches
5. Verify rollback success
6. Disable maintenance mode
7. Investigate issues in development environment

**Rollback Time Window**: Within first 24 hours of deployment while original backup is fresh.

## Future Enhancements

1. **Audit Trail**: Track all changes to masterlist entries with before/after snapshots
2. **Bulk Import**: Update CSV import to handle encrypted fields and new relationships
3. **Self-Service Identity Updates**: Allow users to request identity changes with admin approval workflow
4. **Email Verification**: Require email verification when masterlist email changes
5. **Region-Based Features**: Use region data for analytics or region-specific functionality
6. **Search Optimization**: Implement full-text search on encrypted fields using Algolia or Meilisearch
7. **Key Rotation**: Implement automated encryption key rotation with re-encryption pipeline

---

## Summary of Key Design Decisions

1. **Foreign Key Relationship**: Chose `belongsTo/hasOne` over many-to-many to enforce one-user-per-masterlist-entry constraint
2. **Email Hash for Uniqueness**: Added `email_hash` column to enable fast uniqueness checks without decrypting all emails
3. **Accessor Methods for Backward Compatibility**: Implemented accessors on User model to minimize immediate refactoring needs
4. **Login via Masterlist Lookup**: Changed login to find masterlist by email hash first, then get associated user
5. **Cascading Delete**: Deleting masterlist entry cascades to user (with confirmation requirement)
6. **Read-Only Identity Fields**: Users cannot self-update identity data; only admin/staff can update masterlist
7. **Encrypted Casting**: Used Laravel's built-in encrypted casting for transparent encryption/decryption
8. **Region Dropdown**: Hardcoded list of 17 Philippine regions (not dynamic) for simplicity and reliability
9. **Nullable masterlist_id During Migration**: Kept nullable during migration to handle edge cases, with post-migration audit
10. **Cache Strategy**: Recommended caching for profile data to offset encryption performance cost

---

**Estimated Implementation Time**: 
- Backend: 12-16 hours
- Frontend: 4-6 hours
- Testing: 8-10 hours
- Total: 24-32 hours

**Risk Level**: Medium-High (database restructuring with encryption is non-trivial, requires careful testing and backup strategy)
