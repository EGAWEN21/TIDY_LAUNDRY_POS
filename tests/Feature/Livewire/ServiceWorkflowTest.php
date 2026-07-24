<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Service\ServiceEdit;
use App\Livewire\Service\ServiceManage;
use App\Models\Service;
use App\Models\ServiceDetail;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authorized_staff_can_create_a_service_with_a_type_and_price(): void
    {
        $user = User::firstOrFail();
        $serviceType = ServiceType::create([
            'service_type_name' => 'Standard Wash',
            'is_active' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(ServiceManage::class)
            ->set('service_name', 'Wash and Fold')
            ->set('imageicon', ['path' => 'existing-icon.png'])
            ->call('add', 1)
            ->set('servicetypes.2', $serviceType->id)
            ->set('prices.2', 125)
            ->call('save')
            ->assertRedirect(route('service'));

        $service = Service::where('service_name', 'Wash and Fold')->firstOrFail();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'icon' => 'existing-icon.png',
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('service_details', [
            'service_id' => $service->id,
            'service_type_id' => $serviceType->id,
            'service_price' => 125,
        ]);
    }

    public function test_service_creation_requires_an_icon_and_a_service_type(): void
    {
        $user = User::firstOrFail();

        Livewire::actingAs($user)
            ->test(ServiceManage::class)
            ->set('service_name', 'Missing Icon Service')
            ->call('save')
            ->assertHasErrors(['icon']);

        Livewire::actingAs($user)
            ->test(ServiceManage::class)
            ->set('service_name', 'Missing Type Service')
            ->set('imageicon', ['path' => 'existing-icon.png'])
            ->call('save')
            ->assertHasErrors(['inputerror']);

        $this->assertDatabaseMissing('services', [
            'service_name' => 'Missing Icon Service',
        ]);
        $this->assertDatabaseMissing('services', [
            'service_name' => 'Missing Type Service',
        ]);
    }

    public function test_service_edit_replaces_service_details(): void
    {
        $user = User::firstOrFail();
        $oldType = ServiceType::create([
            'service_type_name' => 'Old Type',
            'is_active' => 1,
        ]);
        $newType = ServiceType::create([
            'service_type_name' => 'New Type',
            'is_active' => 1,
        ]);
        $service = Service::create([
            'service_name' => 'Existing Service',
            'icon' => 'old-icon.png',
            'is_active' => 1,
        ]);
        ServiceDetail::create([
            'service_id' => $service->id,
            'service_type_id' => $oldType->id,
            'service_price' => 50,
        ]);

        Livewire::actingAs($user)
            ->test(ServiceEdit::class, ['id' => $service->id])
            ->set('service_name', 'Updated Service')
            ->set('imageicon', ['path' => 'new-icon.png'])
            ->set('servicetypes.2', $newType->id)
            ->set('prices.2', 90)
            ->call('save')
            ->assertRedirect('/admin/service');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'service_name' => 'Updated Service',
            'icon' => 'new-icon.png',
        ]);
        $this->assertDatabaseMissing('service_details', [
            'service_id' => $service->id,
            'service_type_id' => $oldType->id,
        ]);
        $this->assertDatabaseHas('service_details', [
            'service_id' => $service->id,
            'service_type_id' => $newType->id,
            'service_price' => 90,
        ]);
    }

    public function test_an_assigned_service_icon_cannot_be_deleted(): void
    {
        $user = User::firstOrFail();
        $filename = 'assigned-' . Str::random(8) . '.png';
        $path = public_path('assets/img/service-icons/' . $filename);
        File::copy(public_path('assets/img/service-icons/' . $this->existingIcon()), $path);
        Service::create([
            'service_name' => 'Icon Assignment',
            'icon' => $filename,
            'is_active' => 1,
        ]);

        try {
            Livewire::actingAs($user)
                ->test(ServiceEdit::class, ['id' => Service::firstOrFail()->id])
                ->call('deleteIcon', $filename)
                ->assertDispatched('alert');

            $this->assertFileExists($path);
        } finally {
            File::delete($path);
        }
    }

    private function existingIcon(): string
    {
        return collect(File::files(public_path('assets/img/service-icons')))
            ->map(fn ($file) => $file->getFilename())
            ->firstOrFail();
    }
}
