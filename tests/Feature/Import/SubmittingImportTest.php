<?php

namespace Tests\Feature\Import;

use Tests\TestCase;
use Illuminate\Http\UploadedFile as IlluminateUploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Import;

class SubmittingImportTest extends TestCase
{
    use RefreshDatabase, ImportHelper;

    /** @test */
    public function authenticate_user_can_see_transaction_import_page()
    {
        $this->signIn();

        $this->get('transactions/import')->assertStatus(200);
    }

    /** @test */
    public function guest_can_not_see_transaction_import_page()
    {
        $this->get('transactions/import')->assertRedirect('login');
    }

    /** @test */
    public function authenticate_user_can_initiate_import_by_uploading_csv_format()
    {
        $user = $this->signIn();

        Storage::fake('local');

        // Arrange the fake csv file
        $file = IlluminateUploadedFile::fake()->create('transactions.csv', 250);

        // Act to post the import
        $response = $this->post('transactions/import', ['file' => $file]);

        // Assert that we put the uplaoded file into imports folder
        Storage::disk('local')->assertExists('imports/' . $file->hashName());

        // Asset that import has been recorded into the database
        $this->assertDatabaseHas('imports', [
            'file' => $file->hashName(),
            'status' => 'submitted'
        ]);

        // Assert that user has one import
        $this->assertCount(1, $user->imports);

        // Assert that we redirect the user to csv mapping page after import submission
        $response->assertRedirect(route('transactions.import.mapping'));
    }

    /** @test */
    public function authenticate_user_can_not_create_another_import_if_one_is_already_in_progress()
    {
        $user = $this->signIn();

        Storage::fake('local');

        $this->submitTestImportFor($user, 'imports/testimport.csv', Storage::fake('local'));

        // Act to create second import
        $file = IlluminateUploadedFile::fake()->create('transactions.csv', 250);
        $response = $this->post('transactions/import', ['file' => $file]);

        $response->assertStatus(429);
    }

    /** @test */
    public function redirect_authenticate_user_to_import_mapping_page_if_import_with_submitted_status_exists()
    {
        $user = $this->signIn();

        // Arrange an import with status submitted
        $this->submitTestImportFor($user, 'imports/testimport.csv', Storage::fake('local'));

        // Act to post the import
        $response = $this->get('transactions/import');

        // Assert that user redirected to mapping page
        $response->assertRedirect(route('transactions.import.mapping'));
    }

    /** @test */
    public function guest_can_not_initiate_import_by_uploading_csv()
    {
        $file = IlluminateUploadedFile::fake()->create('transactions.csv', 250);

        $response = $this->post('transactions/import', ['file' => $file]);

        $response->assertRedirect('login');
    }

    /** @test */
    public function file_is_required_for_import()
    {
        $this->signIn();

        $response = $this->post('transactions/import', ['file' => null]);

        $response->assertSessionHasErrors('file');
    }

    /** @test */
    public function file_must_have_csv_or_txt_extension_for_import()
    {
        $this->signIn();

        $file = IlluminateUploadedFile::fake()->create('transactions.notcsv', 250);

        $response = $this->post('transactions/import', ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }
}
