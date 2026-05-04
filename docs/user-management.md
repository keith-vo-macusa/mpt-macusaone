# Tài liệu Triển khai Hệ thống Quản trị Người dùng & Hoạt động

Tài liệu này tóm tắt các tính năng và cấu trúc đã được triển khai trong dự án Laravel 12 & Filament v5.

## 1. Hệ thống Phân quyền (Roles & Permissions)
*   **Thư viện**: `spatie/laravel-permission` & `althinect/filament-spatie-roles-permissions`.
*   **Tính năng**:
    *   Phân quyền dựa trên Role và Permission.
    *   **Super Admin**: Tự động cấp mọi quyền truy cập (Cấu hình trong `AppServiceProvider`).
    *   Giao diện quản lý Role/Permission tích hợp trong menu Settings.

## 2. Quản lý Người dùng (User Management)
*   **File**: `app/Filament/Resources/Users/UserResource.php`
*   **Tính năng**:
    *   CRUD đầy đủ cho User.
    *   **Trạng thái Active**: Cho phép kích hoạt hoặc vô hiệu hoá tài khoản.
    *   **Bảo mật**: 
        *   Ngăn chặn Super Admin tự xoá hoặc tự vô hiệu hoá chính mình.
        *   Mật khẩu được tự động hash khi lưu.
    *   **Gán quyền**: Cho phép gán nhiều Role cho User ngay tại trang tạo/sửa.

## 3. Quản lý Hồ sơ Cá nhân (Profile Management)
*   **File**: `app/Filament/Pages/Auth/EditProfile.php`
*   **Tính năng**:
    *   Trang chỉnh sửa hồ sơ tùy chỉnh (chia làm các Section: Thông tin, Mật khẩu, Bảo mật).
    *   **Xác thực mật khẩu**: Yêu cầu nhập mật khẩu hiện tại khi muốn thay đổi các thông tin quan trọng.
    *   **Giao diện**: Tích hợp Sidebar đầy đủ để đồng nhất với trải nghiệm người dùng trong Panel.

## 4. Theo dõi Hoạt động (Tracking & Logging)
### a. Nhật ký Đăng nhập (Last Login)
*   **Database**: Cột `last_login_at` và `last_login_ip`.
*   **Logic**: Sử dụng Listener `UpdateLastLoginListener` lắng nghe sự kiện `Login`.
*   **Hiển thị**: Xem thời gian và IP đăng nhập cuối cùng ngay tại danh sách User.

### b. Nhật ký Hoạt động (Activity Log)
*   **Thư viện**: `spatie/laravel-activitylog`.
*   **Resource**: `app/Filament/Resources/ActivityLogs/ActivityLogResource.php`.
*   **Tính năng**:
    *   Tự động ghi lại mọi thay đổi (Create, Update, Delete) trên Model `User`.
    *   **Timeline View**: Giao diện dòng thời gian trực quan (nút "Lịch sử" trong danh sách User) hiển thị chi tiết thay đổi (Giá trị cũ → Giá trị mới).
    *   **Tối ưu**: Loại bỏ các log không cần thiết (như cập nhật last_login) để tránh làm loãng dữ liệu.

## 5. Các tệp tin cấu hình quan trọng
| Thành phần | Đường dẫn |
| :--- | :--- |
| **Model chính** | `app/Models/User.php` |
| **Giao diện Resource** | `app/Filament/Resources/Users/UserResource.php` |
| **Trang Profile** | `app/Filament/Pages/Auth/EditProfile.php` |
| **View Timeline** | `resources/views/filament/components/activity-timeline.blade.php` |
| **View So sánh Dữ liệu** | `resources/views/filament/components/activity-log-properties.blade.php` |
| **Panel Provider** | `app/Providers/Filament/AdminPanelProvider.php` |

---
*Tài liệu được tạo tự động bởi Antigravity AI.*
