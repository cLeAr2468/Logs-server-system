<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Mail\TransactionStatusMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TransactionController extends Controller
{
    /**
     * Get all transactions (admin)
     */
    public function index()
    {
        $transactions = Transaction::with('user')->latest()->get();

        return response()->json([
            'message' => 'Transactions retrieved successfully',
            'transactions' => $transactions
        ], 200);
    }

    /**
     * Get authenticated user's transactions
     */
    public function getUserTransactions(Request $request)
    {
        $transactions = Transaction::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Your transactions retrieved successfully',
            'transactions' => $transactions
        ], 200);
    }

    /**
     * Create a new appointment/transaction
     */
    public function store(Request $request)
    {
        $request->validate([
            'purpose' => 'required|string',
            'street_house_no' => 'required|string',
            'brgy' => 'required|string',
            'municipality' => 'required|string',
            'province' => 'required|string',
            'schedule_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required|string',
        ]);

        // Check if user already has a pending or approved appointment with the same purpose
        $existingPurposeAppointment = Transaction::where('user_id', $request->user()->id)
            ->where('purpose', $request->purpose)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingPurposeAppointment) {
            return response()->json([
                'message' => 'You already have a pending or approved appointment for "' . $request->purpose . '". Please wait for it to be completed or cancelled before creating a new one with the same purpose.'
            ], 409); // 409 Conflict
        }

        // Check if user already has a pending or approved appointment on the same date and time
        $existingTimeSlot = Transaction::where('user_id', $request->user()->id)
            ->where('schedule_date', $request->schedule_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingTimeSlot) {
            return response()->json([
                'message' => 'You already have an appointment on this date and time slot.'
            ], 409); // 409 Conflict
        }

        // Create transaction
        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'purpose' => $request->purpose,
            'street_house_no' => $request->street_house_no,
            'brgy' => $request->brgy,
            'municipality' => $request->municipality,
            'province' => $request->province,
            'schedule_date' => $request->schedule_date,
            'time_slot' => $request->time_slot,
            'status' => 'pending',
        ]);

        // Load the user relationship
        $transaction->load('user');

        return response()->json([
            'message' => 'Appointment created successfully',
            'transaction' => $transaction
        ], 201);
    }

    /**
     * Get a single transaction
     */
    public function show($id)
    {
        $transaction = Transaction::with('user')->find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Transaction retrieved successfully',
            'transaction' => $transaction
        ], 200);
    }

    /**
     * Update user's own appointment
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'purpose' => 'sometimes|required|string',
            'street_house_no' => 'sometimes|required|string',
            'brgy' => 'sometimes|required|string',
            'municipality' => 'sometimes|required|string',
            'province' => 'sometimes|required|string',
            'schedule_date' => 'sometimes|required|date|after_or_equal:today',
            'time_slot' => 'sometimes|required|string',
        ]);

        // Find transaction
        $transaction = Transaction::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found or you do not have permission to edit this appointment'
            ], 404);
        }

        // Only allow editing if status is pending
        if ($transaction->status !== 'pending') {
            return response()->json([
                'message' => 'You can only edit pending appointments'
            ], 400);
        }

        // Check if purpose changed
        $purposeChanged = $request->has('purpose') && $request->purpose !== $transaction->purpose;

        // If purpose changed, check for duplicate purpose
        if ($purposeChanged) {
            $existingPurposeAppointment = Transaction::where('user_id', $request->user()->id)
                ->where('purpose', $request->purpose)
                ->where('id', '!=', $id) // Exclude current appointment
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existingPurposeAppointment) {
                return response()->json([
                    'message' => 'You already have a pending or approved appointment for "' . $request->purpose . '"'
                ], 409);
            }
        }

        // Check if time slot changed
        $timeSlotChanged = ($request->has('schedule_date') && $request->schedule_date !== $transaction->schedule_date) ||
                          ($request->has('time_slot') && $request->time_slot !== $transaction->time_slot);

        // If time slot changed, check for conflicts
        if ($timeSlotChanged) {
            $existingTimeSlot = Transaction::where('user_id', $request->user()->id)
                ->where('schedule_date', $request->schedule_date ?? $transaction->schedule_date)
                ->where('time_slot', $request->time_slot ?? $transaction->time_slot)
                ->where('id', '!=', $id) // Exclude current appointment
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existingTimeSlot) {
                return response()->json([
                    'message' => 'You already have an appointment on this date and time slot'
                ], 409);
            }
        }

        // Update transaction
        if ($request->has('purpose')) {
            $transaction->purpose = $request->purpose;
        }
        if ($request->has('street_house_no')) {
            $transaction->street_house_no = $request->street_house_no;
        }
        if ($request->has('brgy')) {
            $transaction->brgy = $request->brgy;
        }
        if ($request->has('municipality')) {
            $transaction->municipality = $request->municipality;
        }
        if ($request->has('province')) {
            $transaction->province = $request->province;
        }
        if ($request->has('schedule_date')) {
            $transaction->schedule_date = $request->schedule_date;
        }
        if ($request->has('time_slot')) {
            $transaction->time_slot = $request->time_slot;
        }

        $transaction->save();
        $transaction->load('user');

        return response()->json([
            'message' => 'Appointment updated successfully',
            'transaction' => $transaction
        ], 200);
    }

    /**
     * Update transaction status (admin)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,completed,cancelled,rejected'
        ]);

        $transaction = Transaction::with('user')->find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found'
            ], 404);
        }

        $oldStatus = $transaction->status;
        $newStatus = $request->status;

        // Send email notification FIRST if status changes to approved, rejected, or completed
        if (in_array($newStatus, ['approved', 'rejected', 'completed']) && $oldStatus !== $newStatus) {
            try {
                $user = $transaction->user;
                
                if (!$user) {
                    return response()->json([
                        'message' => 'User not found for this transaction',
                        'error' => 'user_not_found'
                    ], 404);
                }
                
                $studentName = $user->fname . ' ' . $user->lname;

                // Try to send email FIRST (before changing status)
                Mail::to($user->email)->send(new TransactionStatusMail($transaction, $newStatus, $studentName));
                
                // Log successful email send
                \Log::info('Transaction status email sent successfully', [
                    'transaction_id' => $transaction->id,
                    'email' => $user->email,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'student_name' => $studentName
                ]);
                
            } catch (\Exception $e) {
                // Log detailed error
                \Log::error('Failed to send transaction status email', [
                    'transaction_id' => $transaction->id,
                    'email' => $transaction->user->email ?? 'unknown',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'mail_driver' => config('mail.default'),
                    'from_address' => config('mail.from.address')
                ]);
                
                // Return error without changing status
                return response()->json([
                    'message' => 'Failed to send email notification. Transaction status was not changed.',
                    'error' => 'email_send_failed',
                    'details' => config('app.debug') ? $e->getMessage() : 'Email service temporarily unavailable',
                    'transaction_id' => $transaction->id,
                    'attempted_status' => $newStatus
                ], 500);
            }
        }

        // Only update status if email was sent successfully (or no email needed)
        $transaction->status = $newStatus;
        $transaction->save();

        return response()->json([
            'message' => 'Transaction status updated successfully',
            'transaction' => $transaction,
            'email_sent' => in_array($newStatus, ['approved', 'rejected', 'completed']) && $oldStatus !== $newStatus
        ], 200);
    }

    /**
     * Cancel user's own transaction
     */
    public function cancel(Request $request, $id)
    {
        $transaction = Transaction::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found or you do not have permission to cancel this appointment'
            ], 404);
        }

        if ($transaction->status === 'completed') {
            return response()->json([
                'message' => 'Cannot cancel a completed appointment'
            ], 400);
        }

        $transaction->status = 'cancelled';
        $transaction->save();

        return response()->json([
            'message' => 'Appointment cancelled successfully',
            'transaction' => $transaction
        ], 200);
    }

    /**
     * Delete a transaction (admin only)
     */
    public function destroy($id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found'
            ], 404);
        }

        $transaction->delete();

        return response()->json([
            'message' => 'Transaction deleted successfully'
        ], 200);
    }

    /**
     * Get available time slots for a specific date
     */
    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today'
        ]);

        $allSlots = [
            'morning' => [
                '09:00 AM',
                '09:30 AM',
                '10:00 AM',
                '10:30 AM',
                '11:00 AM',
                '11:30 AM',
            ],
            'afternoon' => [
                '01:00 PM',
                '01:30 PM',
                '02:00 PM',
                '02:30 PM',
                '03:00 PM',
                '03:30 PM',
            ]
        ];

        // Get booked slots for the date
        $bookedSlots = Transaction::where('schedule_date', $request->date)
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('time_slot')
            ->toArray();

        // Filter available slots
        $availableSlots = [
            'morning' => array_values(array_diff($allSlots['morning'], $bookedSlots)),
            'afternoon' => array_values(array_diff($allSlots['afternoon'], $bookedSlots))
        ];

        return response()->json([
            'message' => 'Available slots retrieved successfully',
            'date' => $request->date,
            'available_slots' => $availableSlots,
            'booked_slots' => $bookedSlots
        ], 200);
    }

    /**
     * Validate and get user by student_id (for admin/staff)
     */
    public function validateStudentId(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string'
        ]);

        $user = User::where('student_id', $request->student_id)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Student ID not found in the database',
                'found' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Student found',
            'found' => true,
            'user' => [
                'id' => $user->id,
                'student_id' => $user->student_id,
                'fname' => $user->fname,
                'mname' => $user->mname,
                'lname' => $user->lname,
                'course' => $user->course,
                'year_level' => $user->year_level,
                'email' => $user->email,
            ]
        ], 200);
    }

    /**
     * Create transaction by admin/staff for a user
     */
    public function storeByAdmin(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string|exists:users,student_id',
            'purpose' => 'required|string',
            'street_house_no' => 'required|string',
            'brgy' => 'required|string',
            'municipality' => 'required|string',
            'province' => 'required|string',
            'schedule_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required|string',
        ]);

        // Find user by student_id
        $user = User::where('student_id', $request->student_id)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User with this Student ID not found'
            ], 404);
        }

        // Check if user already has a pending or approved appointment with the same purpose
        $existingPurposeAppointment = Transaction::where('user_id', $user->id)
            ->where('purpose', $request->purpose)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingPurposeAppointment) {
            return response()->json([
                'message' => 'This student already has a pending or approved appointment for "' . $request->purpose . '". Please wait for it to be completed or cancelled before creating a new one with the same purpose.'
            ], 409);
        }

        // Check if the time slot is already booked
        $existingTimeSlot = Transaction::where('schedule_date', $request->schedule_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingTimeSlot) {
            return response()->json([
                'message' => 'This time slot is already booked for the selected date.'
            ], 409);
        }

        // Create transaction with status 'approved' (Processing) since admin/staff created it
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'purpose' => $request->purpose,
            'street_house_no' => $request->street_house_no,
            'brgy' => $request->brgy,
            'municipality' => $request->municipality,
            'province' => $request->province,
            'schedule_date' => $request->schedule_date,
            'time_slot' => $request->time_slot,
            'status' => 'approved', // Admin/Staff created = automatically approved (Processing)
        ]);

        // Load the user relationship
        $transaction->load('user');

        return response()->json([
            'message' => 'Transaction created successfully with Processing status',
            'transaction' => $transaction
        ], 201);
    }
}
