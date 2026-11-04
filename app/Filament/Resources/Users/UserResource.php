<?php

namespace App\Filament\Resources\Users;

use App\Enums\Papel;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Só os administradores gerem utilizadores. */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $modelLabel = 'utilizador';

    protected static ?string $pluralModelLabel = 'utilizadores';

    protected static ?string $slug = 'utilizadores';

    public static function canViewAny(): bool
    {
        return auth()->user()?->ehAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny() && ! $record->is(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nome')->required()->maxLength(255),
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                Select::make('papel')->label('Papel')->options(Papel::class)->default(Papel::Editor)->required()
                    ->helperText('Editores tratam o conteúdo; administradores também gerem utilizadores e apagam peças.'),
                TextInput::make('password')->label('Palavra-passe')->password()->revealable()
                    ->minLength(10)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Deixe vazio para não mudar.' : null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('papel')->label('Papel')->badge(),
                TextColumn::make('created_at')->label('Criado')->date('d/m/Y'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
