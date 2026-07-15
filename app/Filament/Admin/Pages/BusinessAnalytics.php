<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesFeaturePage;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Models\Machine;
use App\Services\Analytics\BusinessAnalyticsService;
use App\Services\Users\UserCloudScope;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class BusinessAnalytics extends Page implements HasForms
{
    use AuthorizesFeaturePage;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Sales & profit';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::Reports;

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Business Analytics';

    protected static ?string $slug = 'reports/business-analytics';

    protected string $view = 'filament.admin.pages.business-analytics';

    public function getSubheading(): ?string
    {
        return 'Filter by date range and vending machines. View combined portfolio or per-unit profit.';
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'period' => '30d',
            'machine_ids' => [],
            'view_mode' => 'portfolio',
            'from' => now()->subDays(29)->toDateString(),
            'to' => now()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filters')
                    ->description('View per vending unit (1 VM) or combined portfolio (all selected machines).')
                    ->schema([
                        Select::make('period')
                            ->label('Period')
                            ->options([
                                '7d' => 'Last 7 days',
                                '30d' => 'Last 30 days',
                                '90d' => 'Last 90 days',
                                'custom' => 'Custom range',
                            ])
                            ->native(false)
                            ->live()
                            ->required(),
                        DatePicker::make('from')
                            ->label('From')
                            ->native(false)
                            ->visible(fn (callable $get): bool => $get('period') === 'custom'),
                        DatePicker::make('to')
                            ->label('To')
                            ->native(false)
                            ->visible(fn (callable $get): bool => $get('period') === 'custom'),
                        CheckboxList::make('machine_ids')
                            ->label('Vending machines (VMs)')
                            ->helperText('Leave empty to include all machines you can access. Select 1 VM or multiple (e.g. 5 VMs) for combined analytics.')
                            ->options(fn (): array => app(UserCloudScope::class)
                                ->scopeMachines(Machine::query())
                                ->orderBy('machine_name')
                                ->get()
                                ->mapWithKeys(fn (Machine $machine): array => [
                                    $machine->id => $machine->machine_name.' ('.$machine->machine_number.')',
                                ])
                                ->all())
                            ->columns(2)
                            ->searchable()
                            ->live(),
                        Select::make('view_mode')
                            ->label('View')
                            ->options([
                                'portfolio' => 'Combined (all selected VMs)',
                                'per_unit' => 'Per unit (each VM separately)',
                            ])
                            ->native(false)
                            ->live()
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewMode(): string
    {
        $state = $this->form->getState();

        return (string) ($state['view_mode'] ?? 'portfolio');
    }

    public function getReport(): array
    {
        $state = $this->form->getState();
        [$from, $to] = $this->resolvePeriod($state);

        $machineIds = array_values(array_map(
            intval(...),
            $state['machine_ids'] ?? [],
        ));

        return app(BusinessAnalyticsService::class)->buildReport($from, $to, $machineIds);
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Reports;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(array $state): array
    {
        return match ($state['period'] ?? '30d') {
            '7d' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '90d' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'custom' => [
                Carbon::parse($state['from'] ?? now()->subDays(29))->startOfDay(),
                Carbon::parse($state['to'] ?? now())->endOfDay(),
            ],
            default => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
        };
    }
}
