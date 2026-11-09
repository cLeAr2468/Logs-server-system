<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 1. Change user_id to student_id (if needed)
     * 2. Change purpose string to purpose_id foreign key (pure relationship)
     */
    public function up(): void
    {
        // Step 1: Check if user_id column exists and rename to student_id
        if (Schema::hasColumn('transactions', 'user_id')) {
            // Drop foreign key constraint on user_id if it exists
            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }

            // Rename user_id to student_id
            Schema::table('transactions', function (Blueprint $table) {
                $table->renameColumn('user_id', 'student_id');
            });

            // Change student_id to string type and add foreign key constraint
            DB::statement('ALTER TABLE transactions MODIFY student_id VARCHAR(255) NOT NULL');
            
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('student_id')->references('student_id')->on('users')->onDelete('cascade');
            });
        }

        // Step 2: Add new purpose_id column
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('purpose_id')->nullable()->after('student_id');
        });

        // Step 3: Migrate existing purpose names to purpose_id (only if purpose column exists)
        if (Schema::hasColumn('transactions', 'purpose')) {
            $transactions = DB::table('transactions')->get();
            
            foreach ($transactions as $transaction) {
                if ($transaction->purpose) {
                    // Find or create purpose
                    $purpose = DB::table('purposes')->where('name', $transaction->purpose)->first();
                    
                    if (!$purpose) {
                        // Create new purpose if it doesn't exist
                        $purposeId = DB::table('purposes')->insertGetId([
                            'name' => $transaction->purpose,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $purposeId = $purpose->id;
                    }
                    
                    // Update transaction with purpose_id
                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update(['purpose_id' => $purposeId]);
                }
            }

            // Step 4: Make purpose_id NOT NULL and add foreign key constraint
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('purpose_id')->nullable(false)->change();
                $table->foreign('purpose_id')->references('id')->on('purposes')->onDelete('restrict');
            });

            // Step 5: Drop old purpose string column
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('purpose');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Add back purpose string column
        if (Schema::hasColumn('transactions', 'purpose_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('purpose')->nullable()->after('purpose_id');
            });

            // Step 2: Migrate purpose_id back to purpose string
            $transactions = DB::table('transactions')->get();
            
            foreach ($transactions as $transaction) {
                if ($transaction->purpose_id) {
                    $purpose = DB::table('purposes')->find($transaction->purpose_id);
                    
                    if ($purpose) {
                        DB::table('transactions')
                            ->where('id', $transaction->id)
                            ->update(['purpose' => $purpose->name]);
                    }
                }
            }

            // Step 3: Drop foreign key and purpose_id column
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign(['purpose_id']);
                $table->dropColumn('purpose_id');
            });
        }

        // Step 4: Revert student_id back to user_id if needed
        if (Schema::hasColumn('transactions', 'student_id')) {
            // Drop foreign key
            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->dropForeign(['student_id']);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist
            }

            // Change back to unsigned big integer
            DB::statement('ALTER TABLE transactions MODIFY student_id BIGINT UNSIGNED NOT NULL');
            
            // Rename column
            Schema::table('transactions', function (Blueprint $table) {
                $table->renameColumn('student_id', 'user_id');
            });
            
            // Add foreign key back to users.id
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }
};
