# Quản lý VPS (VPS Management)

Module này cho phép quản trị viên lưu trữ và quản lý thông tin truy cập các máy chủ ảo (VPS).

## 1. Cấu trúc dữ liệu
*   **Tên VPS**: Nhãn gợi nhớ.
*   **Địa chỉ IP**: Hỗ trợ IPv4.
*   **Cổng (Port)**: Mặc định là 22 (SSH).
*   **Tên đăng nhập**: Mặc định là `root`.
*   **Mật khẩu**: Được mã hóa (`encrypted`) trong database.
*   **Ghi chú**: Thông tin bổ sung.

## 2. Tính năng nổi bật
*   **Bảo mật mật khẩu**: Hỗ trợ chế độ ẩn/hiện mật khẩu trên giao diện Form.
*   **Copy IP**: Click vào địa chỉ IP trong bảng để sao chép nhanh.
*   **Kiểm tra trùng lặp**: Hệ thống tự động chặn nếu nhập trùng tổ hợp **IP + Port**.
*   **Test Connection**: 
    *   Nút "Test" nhanh trong bảng danh sách.
    *   Nút "Kiểm tra kết nối ngay" trong Form tạo/sửa (lấy dữ liệu trực tiếp từ input).

## 3. Nhật ký hoạt động
*   Mọi thay đổi trên VPS (đổi pass, đổi IP, đổi ghi chú) đều được ghi lại trong **Timeline hoạt động**.
*   Truy cập qua nút **"Lịch sử"** trong danh sách VPS.

---
*Cập nhật lần cuối: 04/05/2026*
