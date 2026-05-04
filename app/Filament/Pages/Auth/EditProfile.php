<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public static function isSimple(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin cá nhân')
                    ->description('Cập nhật thông tin cơ bản của bạn.')
                    ->schema([
                        $this->getNameFormComponent()
                            ->maxLength(255),
                        $this->getEmailFormComponent()
                            ->unique(ignoreRecord: true),
                    ])
                    ->columns(2),
                
                Section::make('Mật khẩu')
                    ->description('Đảm bảo tài khoản của bạn sử dụng mật khẩu dài, ngẫu nhiên để giữ an toàn.')
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->columns(2),

                Section::make('Xác nhận bảo mật')
                    ->description('Vui lòng nhập mật khẩu hiện tại để lưu các thay đổi quan trọng.')
                    ->schema([
                        $this->getCurrentPasswordFormComponent(),
                    ])
                    ->visible(fn (Get $get) => filled($get('password')) || ($get('email') !== $this->getUser()->email)),
            ]);
    }
}
