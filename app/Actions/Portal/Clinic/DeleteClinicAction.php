<?php

namespace App\Actions\Portal\Clinic;

use App\Models\Address;

class DeleteClinicAction
{
    public function handle(Address $clinic): void
    {
        $clinic->delete();
    }
}
