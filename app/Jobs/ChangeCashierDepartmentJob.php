<?php

namespace App\Jobs;

use App\Models\Department;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChangeCashierDepartmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Shift $shift;

    /**
     * Create a new job instance.
     */
    public function __construct(Shift $shift)
    {
        $this->shift = $shift;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::findOrFail($this->shift->user_id);

        $linkedDepartment = Department::find($user->department_id)?->linked_department;
        if (! (isset($linkedDepartment) && $linkedDepartment == $this->shift->department_id)) {
            $user->update([
                'department_id' => $this->shift->department_id,
            ]);

            $user->save();
            $this->shift->update([
                'is_closed' => true,
            ]);

            $this->shift->save();
            Log::info('Job chnaged the shift for user '.$this->shift->user_id.' to department '.$this->shift->department_id);
        }
    }
}
