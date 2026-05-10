<?php

namespace Fleetbase\TeraHarvest\Tests\Feature;

use Fleetbase\TeraHarvest\Models\QualityGrade;
use Fleetbase\TeraHarvest\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class QualityGradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_number_auto_generated(): void
    {
        $number = QualityGrade::generateCertificateNumber();
        $this->assertMatchesRegularExpression('/^QC-\d{4}-\d{6}$/', $number);
    }

    public function test_certificate_number_includes_current_year(): void
    {
        $number = QualityGrade::generateCertificateNumber();
        $this->assertStringContainsString((string) now()->year, $number);
    }

    public function test_quality_grade_creates_with_correct_fields(): void
    {
        $grade = QualityGrade::create([
            'uuid'             => (string) Str::uuid(),
            'company_id'       => 'test-co',
            'crop_type'        => 'coffee',
            'grading_standard' => 'ecx',
            'overall_grade'    => 'A',
            'weight_kg'        => '250.500',
            'moisture_pct'     => '11.50',
            'certificate_number' => QualityGrade::generateCertificateNumber(),
            'status'           => 'draft',
            'graded_at'        => now(),
            'created_by'       => 'inspector-1',
        ]);

        $this->assertEquals('A', $grade->overall_grade);
        $this->assertEquals('250.500', $grade->weight_kg);
        $this->assertEquals('ecx', $grade->grading_standard);
    }

    public function test_custom_attributes_stored_as_json(): void
    {
        $grade = QualityGrade::create([
            'uuid'             => (string) Str::uuid(),
            'company_id'       => 'test-co',
            'crop_type'        => 'teff',
            'grading_standard' => 'custom',
            'overall_grade'    => 'B',
            'weight_kg'        => '100.000',
            'certificate_number' => 'QC-TEST-000001',
            'status'           => 'draft',
            'graded_at'        => now(),
            'custom_attributes'=> ['purity_pct' => 98.5, 'origin_verified' => true],
        ]);

        $fresh = $grade->fresh();
        $this->assertIsArray($fresh->custom_attributes);
        $this->assertEquals(98.5, $fresh->custom_attributes['purity_pct']);
    }
}
