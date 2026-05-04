# Giám sát & Thống kê (Monitoring & Stats)

Hệ thống cung cấp khả năng tự động theo dõi trạng thái hoạt động của các VPS và thống kê dữ liệu trực quan.

## 1. Giám sát tự động (Automatic Monitoring)
*   **Lệnh Console**: `php artisan vps:check`
*   **Tần suất**: Chạy mỗi **5 phút** một lần.
*   **Cấu hình lập lịch**: Nằm tại `routes/console.php`.
*   **Logic hoạt động**:
    1. Quét toàn bộ VPS đang ở trạng thái "Kích hoạt".
    2. Thử kết nối Socket tới IP:Port.
    3. Cập nhật cột `is_online` và `last_checked_at` trong database.

## 2. Hệ thống Cảnh báo (Alerts)
*   Khi một VPS đang `Online` chuyển sang trạng thái `Offline`, hệ thống sẽ gửi thông báo khẩn cấp.
*   **Đối tượng nhận**: Toàn bộ người dùng có role `Super Admin`.
*   **Kênh nhận**: Chuông thông báo (Database Notification) ở góc phải màn hình quản trị.

## 3. Dashboard Widget
*   Trang chủ Dashboard hiển thị Widget **VpsStats**:
    *   **Tổng số VPS**: Tổng quy mô hệ thống.
    *   **VPS đang Online**: Số máy chủ đang hoạt động tốt.
    *   **VPS đang Offline**: Cảnh báo đỏ nếu có máy chủ mất kết nối.
    *   **Người quản trị**: Tổng số Admin hệ thống.

---
*Cập nhật lần cuối: 04/05/2026*
