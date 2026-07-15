<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WorkOrders;

use App\Enums\UserRole;
use App\Enums\WorkOrderIssueType;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderReportingStatus;
use App\Enums\WorkOrderStatus;
use App\Filament\Admin\Concerns\EnrichesGlobalSearch;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Resources\WorkOrders\Pages\ManageWorkOrders;
use App\Filament\Admin\Resources\WorkOrders\Pages\ViewSupportTicket;
use App\Filament\Admin\Support\AccessibleTable;
use App\Models\Machine;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Filament\InterconnectedEntityService;
use App\Services\Support\SupportQueueService;
use App\Services\Support\WorkOrderService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
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

final class WorkOrderResource extends Resource
{
    use EnrichesGlobalSearch;

    protected static ?string $model = WorkOrder::class;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::Support;

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'support ticket';

    protected static ?string $pluralModelLabel = 'support tickets';

    protected static ?string $navigationLabel = 'Support queue';

    protected static ?string $recordTitleAttribute = 'work_order_number';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getEloquentQuery(): Builder
    {
        if (request()->string('tab')->toString() === 'all') {
            return parent::getEloquentQuery()
                ->with(['user', 'machine', 'assignee'])
                ->orderByDesc('submitted_at');
        }

        return app(SupportQueueService::class)->queueQuery();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('work_order_number')
                                    ->label('Ticket number')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpanFull(),
                                TextInput::make('device_number')
                                    ->label('Device number')
                                    ->maxLength(100),
                                TextInput::make('device_name')
                                    ->label('Device name')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('associated_account')
                                    ->label('Associated account')
                                    ->maxLength(255),
                                TextInput::make('submitted_by')
                                    ->label('Submitted by')
                                    ->maxLength(255),
                                DateTimePicker::make('submitted_at')
                                    ->label('Submission time')
                                    ->required()
                                    ->seconds(false)
                                    ->default(now())
                                    ->columnSpanFull(),
                                Textarea::make('issue_description')
                                    ->label('Issue description')
                                    ->rows(4)
                                    ->columnSpanFull(),
                                Select::make('issue_type')
                                    ->label('Issue type')
                                    ->options(WorkOrderIssueType::class)
                                    ->native(false)
                                    ->searchable()
                                    ->nullable(),
                                Select::make('assigned_to_user_id')
                                    ->label('Assigned agent')
                                    ->options(
                                        User::query()
                                            ->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all()
                                    )
                                    ->searchable()
                                    ->nullable(),
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
                                    ->nullable()
                                    ->columnSpanFull(),
                                TextInput::make('user_rating')
                                    ->label('User rating')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->nullable(),
                                Select::make('priority')
                                    ->label('Priority')
                                    ->options(WorkOrderPriority::class)
                                    ->native(false)
                                    ->searchable()
                                    ->required()
                                    ->default(WorkOrderPriority::Normal),
                                Select::make('reporting_status')
                                    ->label('Reporting status')
                                    ->options(WorkOrderReportingStatus::class)
                                    ->native(false)
                                    ->searchable()
                                    ->required()
                                    ->default(WorkOrderReportingStatus::None),
                                Select::make('status')
                                    ->label('Status')
                                    ->options(WorkOrderStatus::class)
                                    ->native(false)
                                    ->searchable()
                                    ->required()
                                    ->default(WorkOrderStatus::Unprocessed),
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
                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (?WorkOrderPriority $state): string => $state?->color() ?? 'gray')
                    ->sortable()
                    ->formatStateUsing(fn (?WorkOrderPriority $state): string => $state?->getLabel() ?? ''),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?WorkOrderStatus $state): string => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn (?WorkOrderStatus $state): string => $state?->getLabel() ?? ''),
                TextColumn::make('device_name')
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
                TextColumn::make('submitted_by')
                    ->label('Submitted by')
                    ->searchable(),
                TextColumn::make('assignee.name')
                    ->label('Assigned to')
                    ->placeholder('Unassigned'),
                TextColumn::make('issue_type')
                    ->label('Issue type')
                    ->badge()
                    ->toggleable()
                    ->formatStateUsing(fn (?WorkOrderIssueType $state): string => $state?->getLabel() ?? '—'),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('live_chat_active')
                    ->label('Live chat')
                    ->boolean(),
            ])
            ->defaultSort('submitted_at')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(WorkOrderStatus::class),
                SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(WorkOrderPriority::class),
                Filter::make('ticket_number')
                    ->label('Ticket number')
                    ->form([
                        TextInput::make('value')->label('Ticket number')->placeholder('Ticket #…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q): Builder => $q->where('work_order_number', 'like', '%'.$data['value'].'%'),
                    )),
                Filter::make('submitted_by')
                    ->label('Submitted by')
                    ->form([
                        TextInput::make('value')->label('Submitted by')->placeholder('Name or email…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q): Builder => $q->where('submitted_by', 'like', '%'.$data['value'].'%'),
                    )),
                Filter::make('device')
                    ->label('Machine')
                    ->form([
                        TextInput::make('value')->label('Machine name or number')->placeholder('Search machine…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q): Builder => $q->where(function (Builder $scoped) use ($data): void {
                            $scoped
                                ->where('device_name', 'like', '%'.$data['value'].'%')
                                ->orWhere('device_number', 'like', '%'.$data['value'].'%');
                        }),
                    )),
            ])
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make()
                    ->label('Open queue ticket')
                    ->url(fn (WorkOrder $record): string => self::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->modalHeading('Edit support ticket'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Support queue is clear')
            ->emptyStateDescription('New operator tickets will appear here by priority.');

        return AccessibleTable::apply($table, 'Search tickets by number, machine, or submitter…');
    }

    /**
     * @return list<string>
     */
    protected static function globalSearchAttributes(): array
    {
        return ['work_order_number', 'device_name', 'device_number', 'submitted_by', 'issue_description'];
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
            'Priority' => $record->priority?->getLabel(),
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(WorkOrderService::class)->canManageQueue();
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWorkOrders::route('/'),
            'view' => ViewSupportTicket::route('/{record}'),
        ];
    }
}
