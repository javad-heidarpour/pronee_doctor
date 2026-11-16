<?php

namespace App\Actions\Portal\Clinic;

use App\Models\Address;

class UpdateClinicAction
{
    public function handle(Address $clinic, array $data): Address
    {
        $clinic->update($data);

        return $clinic;
    }
}
