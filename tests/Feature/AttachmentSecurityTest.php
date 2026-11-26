<?php

namespace Tests\Feature;

use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function requestWithAttachment(User $owner): PaymentRequest
    {
        $this->actingAs($owner)->post('/payment-requests', [
            'vendor_name' => 'Vendor', 'invoice_number' => 'INV-1', 'amount' => '10',
            'payment_method' => 'cheque', 'payment_date' => now()->toDateString(),
            'attachment' => UploadedFile::fake()->image('receipt.png'),
        ])->assertSessionHasNoErrors();

        return PaymentRequest::sole();
    }

    public function test_files_are_stored_outside_the_public_directory_with_random_names(): void
    {
        Storage::fake('attachments');
        $request = $this->requestWithAttachment(User::factory()->create());

        $this->assertStringNotContainsString('receipt', $request->attachment_path);
        $this->assertStringStartsWith(storage_path('app/private'), config('filesystems.disks.attachments.root'));
        $this->assertFalse(config('filesystems.disks.attachments.serve'));
        $this->assertFalse(config('filesystems.disks.local.serve'));
    }

    public function test_owner_and_approver_can_download_but_others_cannot(): void
    {
        Storage::fake('attachments');
        $owner = User::factory()->create();
        $request = $this->requestWithAttachment($owner);
        $url = route('payment-requests.attachment', $request);

        $this->actingAs($owner)->get($url)->assertOk()->assertDownload('receipt.png');
        $this->actingAs(User::factory()->approver()->create())->get($url)->assertOk();
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();

        auth()->logout();
        $this->get($url)->assertRedirect('/login');
    }

    public function test_replacing_an_attachment_deletes_the_old_file(): void
    {
        Storage::fake('attachments');
        $owner = User::factory()->create();
        $request = $this->requestWithAttachment($owner);
        $oldPath = $request->attachment_path;

        $this->actingAs($owner)->put(route('payment-requests.update', $request), [
            'vendor_name' => 'Vendor', 'invoice_number' => 'INV-1', 'amount' => '10',
            'payment_method' => 'cheque', 'payment_date' => now()->toDateString(),
            'attachment' => UploadedFile::fake()->create('new-invoice.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $request->refresh();
        Storage::disk('attachments')->assertMissing($oldPath);
        Storage::disk('attachments')->assertExists($request->attachment_path);
        $this->assertSame(['old' => 'receipt.png', 'new' => 'new-invoice.pdf'], $request->activities()->first()->changes['attachment_name']);
    }
}
