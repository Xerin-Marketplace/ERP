<?php

namespace Modules\SystemAdministration\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Commercial\Models\Customer;
use Modules\Finance\Models\Supplier;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Company;
use Modules\SystemAdministration\Models\DataImport;
use Modules\SystemAdministration\Services\DataImportService;
use Tests\TestCase;
use Database\Seeders\RolePermissionSeeder;

class DataImportTest extends TestCase
{
    use RefreshDatabase;

    private DataImportService $svc;
    private \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(IdentityDatabaseSeeder::class);
        Storage::fake('local');
        $this->svc = app(DataImportService::class);
        $this->user = \App\Models\User::factory()->create();
    }

    private function csv(array $rows): UploadedFile
    {
        $content = '';
        foreach ($rows as $r) $content .= implode(',', $r) . "\n";
        return UploadedFile::fake()->createWithContent('master.csv', $content);
    }

    public function test_csv_stage_preview_commit_customers(): void
    {
        $import = $this->svc->stage('customers', $this->csv([
            ['Company Name', 'Email Address', 'Phone', 'City'],
            ['Amani Traders', 'amani@x.co', '0712', 'Dar es Salaam'],
            ['Juma Logistics', 'juma@x.co', '0713', 'Arusha'],
        ]), $this->user->id);

        $this->assertSame(['Company Name', 'Email Address', 'Phone', 'City'], $this->svc->headers($import));
        $suggested = $this->svc->suggestMapping('customers', $this->svc->headers($import));
        $this->assertSame('name', $suggested['Company Name']);
        $this->assertSame('email', $suggested['Email Address']);
        $this->assertSame('city', $suggested['City']);

        $result = $this->svc->preview($import, $suggested);
        $this->assertCount(2, $result['rows']);
        $this->assertEmpty($result['errors']);

        $out = $this->svc->commit($import->fresh());
        $this->assertSame(2, $out['imported']);
        $this->assertSame(2, Customer::where('source', 'import')->count());
        $this->assertDatabaseHas('customers', ['name' => 'Amani Traders', 'city' => 'Dar es Salaam']);
    }

    public function test_alias_normalization_and_duplicate_detection(): void
    {
        $this->svc->registerAlias('customers', 'category', 'eletronics', 'Electronics');
        $existing = new Customer(['company_id' => Company::value('id'),
            'name' => 'Existing Co', 'status' => 'active']);
        $existing->forceFill(['code' => 'C-1'])->save();

        $import = $this->svc->stage('customers', $this->csv([
            ['Name', 'Category'],
            ['New Co', 'eletronics'],            // alias → Electronics
            ['Existing Co', 'retail'],           // duplicate in DB
            ['New Co', 'wholesale'],             // duplicate within file
            ['', 'retail'],                      // missing name
        ]), $this->user->id);

        $result = $this->svc->preview($import, ['Name' => 'name', 'Category' => 'category']);
        $this->assertSame('Electronics', $result['rows'][0]['data']['category']);
        $ok = collect($result['rows'])->where('ok', true);
        $this->assertCount(1, $ok);
        $this->assertCount(3, $result['errors']);

        $out = $this->svc->commit($import->fresh());
        $this->assertSame(1, $out['imported']);
        $this->assertSame(3, $out['skipped']);
        $this->assertSame('imported', $import->fresh()->status);
    }

    public function test_supplier_import_and_invalid_email_flagged(): void
    {
        $import = $this->svc->stage('suppliers', $this->csv([
            ['Name', 'Email'],
            ['Good Supplier', 'good@s.co'],
            ['Bad Email', 'not-an-email'],
        ]), $this->user->id);

        $result = $this->svc->preview($import, ['Name' => 'name', 'Email' => 'email']);
        $out = $this->svc->commit($import->fresh());
        $this->assertSame(1, $out['imported']);
        $this->assertDatabaseHas('suppliers', ['name' => 'Good Supplier']);
        $this->assertMatchesRegularExpression('/^XER-SUP-\d{6}$/',
            Supplier::where('name', 'Good Supplier')->value('code'));
    }

    public function test_xlsx_parses(): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([['Name', 'Phone'], ['Excel Co', '0711']], null, 'A1');
        $tmp = tempnam(sys_get_temp_dir(), 'x') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tmp);

        $import = $this->svc->stage('customers',
            new UploadedFile($tmp, 'master.xlsx', null, null, true), $this->user->id);
        $rows = $this->svc->rows($import);
        $this->assertSame('Excel Co', $rows[0]['Name']);
    }
}
