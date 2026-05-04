# Quản lý Domain & Tích hợp Namecheap

Hệ thống cho phép quản lý danh sách tên miền và các bản ghi DNS (Subdomain) thông qua kết nối trực tiếp với Namecheap API.

## 1. Cấu hình hệ thống

Để hệ thống hoạt động, bạn cần cấu hình các thông số sau trong file `.env`:

```env
NAMECHEAP_API_USER=your_username
NAMECHEAP_API_KEY=your_api_key
NAMECHEAP_CLIENT_IP=183.80.150.252
NAMECHEAP_SANDBOX=false
NAMECHEAP_MOCK=false
```

### Lưu ý quan trọng về bảo mật (Whitelist IP)
Namecheap yêu cầu bạn phải Whitelist địa chỉ IP của máy chủ đang chạy ứng dụng.
- Nếu bạn thấy lỗi "Invalid Request IP", hãy copy địa chỉ IP trong Log và thêm vào mục **Whitelist IPs** trong cài đặt API của Namecheap.
- Đừng quên nhấn **Save Changes** sau khi thêm IP.

## 2. Các tính năng chính

### A. Đồng bộ dữ liệu (Sync)
- **Cơ chế**: Hệ thống gọi API `getHosts` để lấy tổng số bản ghi hiện có.
- **Dữ liệu cập nhật**: Cột "Đã dùng" và "Còn lại" sẽ được làm mới dựa trên giới hạn (mặc định 150) của Namecheap.

### B. Xem danh sách Subdomain (Real-time Search)
- Nhấn nút **"DS Subdomain"** để mở Modal.
- Dữ liệu được lấy trực tiếp từ API mỗi khi mở Modal.
- **Tìm kiếm nhanh**: Sử dụng Alpine.js để lọc dữ liệu tức thì trên trình duyệt (Client-side), không gây trễ Server.

### C. Thêm Subdomain mới
- Nhấn nút **"Thêm Subdomain"**.
- Nhập Host (ví dụ: `vps1`), chọn Loại bản ghi (A, CNAME...) và nhập IP đích.
- **Cơ chế an toàn**: Vì Namecheap sử dụng lệnh `setHosts` (ghi đè toàn bộ), code của hệ thống đã được thiết kế để:
    1. Lấy toàn bộ danh sách cũ.
    2. Chèn bản ghi mới vào cuối danh sách.
    3. Gửi lại toàn bộ danh sách lên Namecheap để đảm bảo **không làm mất** các subdomain cũ.

## 3. Cấu trúc mã nguồn

- **Service**: `App\Services\NamecheapService` - Chứa toàn bộ logic gọi API.
- **Resource**: `App\Filament\Resources\Domains\DomainResource` - Giao diện quản lý và các Actions.
- **UI Components**: Sử dụng kết hợp Filament Actions và Alpine.js nhúng trực tiếp trong PHP để đảm bảo tốc độ và tính đồng bộ cao nhất.

## 4. Xử lý sự cố

- **Lỗi không thêm được subdomain**: Kiểm tra Log Laravel (`storage/logs/laravel.log`). Thường do IP bị chặn hoặc tham số API không chính xác.
- **Dữ liệu không khớp**: Nhấn nút "Đồng bộ" trên dòng tương ứng để cập nhật lại số lượng mới nhất từ Namecheap.
