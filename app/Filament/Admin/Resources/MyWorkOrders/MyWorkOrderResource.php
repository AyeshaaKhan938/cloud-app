<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MyWorkOrders;

use App\Enums\UserFeature;
use App\Enums\WorkOrderIssueType;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Filament\Admin\Concerns\AuthorizesUserFeature;
use App\Filament\Admin\Concerns\EnrichesGlobalSearch;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Resources\MyWorkOrders\Pages\ManageMyWorkOrders;
use App\Filament\Admin\Resources\MyWorkOrders\Pages\ViewMyWorkOrder;
use App\Filament\Admin\Support\AccessibleTable;
use App\Models\Machine;
use App\Models\WorkOrder;
use App\Services\Filament\InterconnectedEntityService;
use App\Services\Support\WorkOrderService;
use App\Services\Users\FeatureAccess;
use App\Services\Users\UserCloudScope;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class MyWorkOrderResource extends Resource
{
    use AuthorizesUserFeature;
    use EnrichesGlobalSearch;

    protected static ?string $model = WorkOrder::class;

    protected static ?string $slug = 'my-work-orders';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::Support;

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'My support tickets';

    protected static ?string $modelLabel = 'support ticket';

    protected static ?string $pluralModelLabel = 'support tickets';

    protected static ?string $recordTitleAttribute = 'work_order_number';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['machine', 'assignee'])
            ->where('user_id', auth()->id())
            ->orderByDesc('submitted_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('work_order_number')
                                    ->label('Ticket number')
                                    ->disabled()
                                    ->visibleOn('edit'),
                                Select::make('machine_id')
                                    ->label('Machine')
                                    ->options(
                                        fn (): array => app(UserCloudScope::class)
                                            ->scopeMachines(Machine::query()->orderBy('machine_name'))
                                            ->get()
                                            ->mapWithKeys(fn (Machine $machine): array => [
                                                $machine->id => $machine->machine_name.' ('.$machine->machine_number.')',
                                            ])
                                            ->all()
                                    )
                                    ->searchable()
                                    ->native(false)
                                    ->required()
                                    ->disabledOn('edit'),
                                Select::make('issue_type')
                                    ->label('Issue type')
                                    ->options(WorkOrderIssueType::class)
                                    ->native(false)
                                    ->searchable()
                                    ->required()
                                    ->default(WorkOrderIssueType::MachineIssue),
                                Textarea::make('issue_description')
                                    ->label('Issue description')
                                    ->placeholder('Please enter issue description.')
                                    ->rows(4)
                                    ->columnSpanFull(),
                                FileUpload::make('attachments')
                                    ->label('Attachments')
                                    ->disk('public')
                                    ->directory('work-orders/attachments')
                                    ->multiple()
                                    ->maxFiles(2)
                                    ->maxSize(10240)
                                    ->acceptedFileTypes([
                                        'image/jpeg',
                                        'image/png',
                                        'video/mp4',
                                    ])
                                    ->helperText('Maximum 2 files, 10MB each. Only jpg/png images and mp4 videos allowed.')
                                    ->nullable()
                                    ->columnSpanFull(),
                                Select::make('priority')
                                    ->label('Priority')
                                    ->options(WorkOrderPriority::class)
                                    ->native(false)
                                    ->searchable()
                                    ->required()
                                    ->default(WorkOrderPriority::Normal),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('work_order_number')
                    ->label('Ticket #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('machine.machine_name')
                    ->label('Machine')
                    ->searchable()
                    ->description(fn (WorkOrder $record): ?string => $record->device_number)
                    ->url(function (WorkOrder $record): ?string {
                        if ($record->machine_id) {
                            $machine = Machine::query()->find($record->machine_id);

                            return $machine instanceof Machine
                                ? app(InterconnectedEntityService::class)->machineViewUrl($machine, 'related')
                                : null;
                        }

                        $machine = app(InterconnectedEntityService::class)->findMachineByNumber($record->device_number);

                        return $machine ? app(InterconnectedEntityService::class)->machineViewUrl($machine, 'related') : null;
                    }),
                TextColumn::make('issue_type')
                    ->label('Issue type')
                    ->badge()
                    ->formatStateUsing(fn (?WorkOrderIssueType $state): string => $state?->getLabel() ?? '—')
                    ->toggleable(),
                TextColumn::make('issue_description')
                    ->label('Issue description')
                    ->limit(40)
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : '—'),
                TextColumn::make('submitted_at')
                    ->label('Submission time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (?WorkOrderPriority $state): string => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn (?WorkOrderPriority $state): string => $state?->getLabel() ?? ''),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?WorkOrderStatus $state): string => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn (?WorkOrderStatus $state): string => $state?->getLabel() ?? ''),
                IconColumn::make('live_chat_active')
                    ->label('Live chat')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('work_order_number')
                    ->label('Work order number')
                    ->form([
                        TextInput::make('value')
                            ->label('Work order number')
                            ->placeholder('Work order number'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('work_order_number', 'like', '%'.$data['value'].'%')
                        );
                    }),
                Filter::make('device_number')
                    ->label('Device number')
                    ->form([
                        TextInput::make('value')
                            ->label('Device number')
                            ->placeholder('Device number'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('device_number', 'like', '%'.$data['value'].'%')
                        );
                    }),
                Filter::make('device_name')
                    ->label('Device name')
                    ->form([
                        TextInput::make('value')
                            ->label('Device name')
                            ->placeholder('Device name'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('device_name', 'like', '%'.$data['value'].'%')
                        );
                    }),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(WorkOrderStatus::class),
                SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(WorkOrderPriority::class),
            ])
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make()
                    ->label('Open ticket')
                    ->url(fn (WorkOrder $record): string => self::getUrl('view', ['record' => $record])),
                DeleteAction::make()
                    ->visible(fn (WorkOrder $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No support tickets yet')
            ->emptyStateDescription('Submit a ticket when a machine needs attention.');

        return AccessibleTable::apply($table, 'Search your tickets by number or machine…');
    }

    /**
     * @return list<string>
     */
    protected static function globalSearchAttributes(): array
    {
        return ['work_order_number', 'device_name', 'device_number', 'issue_description'];
    }

    /**
     * @return array<string, string>
     */
    protected static function globalSearchDetails(Model $record): array
    {
        if (! $record instanceof WorkOrder) {
            return [];
        }

        return array_filter([
            'Machine' => $record->device_name,
            'Status' => $record->status?->getLabel(),
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', WorkOrder::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', WorkOrder::class) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMyWorkOrders::route('/'),
            'view' => ViewMyWorkOrder::route('/{record}'),
        ];
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::WorkOrders;
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (app(WorkOrderService::class)->canManageQueue()) {
            return false;
        }

        return app(FeatureAccess::class)->allowsNavigation(self::requiredUserFeature());
    }
}
