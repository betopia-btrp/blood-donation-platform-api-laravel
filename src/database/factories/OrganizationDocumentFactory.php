<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'document_type'   => fake()->randomElement([
                'trade_license',
                'ngo_certificate',
                'tax_certificate',
                'other',
            ]),
            'document_url' => 'https://res.cloudinary.com/demo/raw/upload/' . fake()->uuid() . '.pdf',
        ];
    }
}
