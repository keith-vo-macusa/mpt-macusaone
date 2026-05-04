# Quản trị Người dùng & Phân quyền

Hệ thống quản lý người dùng tập trung với phân quyền chi tiết và bảo mật cao.

## 1. Hệ thống Phân quyền (RBAC)
*   **Role**: Nhóm quyền (ví dụ: Super Admin, Moderator).
*   **Permission**: Các quyền thao tác cụ thể trên từng module.
*   **Super Admin**: Có toàn quyền hệ thống, không bị giới hạn bởi bất kỳ quy tắc nào.

## 2. Quản lý Người dùng (User Resource)
*   **CRUD**: Tạo, sửa, xóa người dùng.
*   **Trạng thái**: Kích hoạt hoặc Vô hiệu hóa tài khoản.
*   **Bảo mật**:
    *   Ngăn chặn việc Super Admin tự xóa hoặc tự khóa chính mình.
    *   Nhật ký đăng nhập (IP và thời gian cuối cùng).

## 3. Hồ sơ Cá nhân (Profile)
*   Trang chỉnh sửa thông tin riêng cho từng người dùng.
*   Yêu cầu xác nhận mật khẩu hiện tại khi thay đổi thông tin quan trọng hoặc mật khẩu mới.

## 4. Nhật ký Hoạt động (Activity Logs)
*   Ghi lại mọi biến động dữ liệu trên Model User.
*   Giao diện **Timeline** trực quan giúp so sánh giá trị cũ và mới.

---
*Cập nhật lần cuối: 04/05/2026*
