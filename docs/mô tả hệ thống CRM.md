# Mô tả hệ thống CRM Telesale

**URL:** [https://crm.ieltscheckmate.com](https://crm.ieltscheckmate.com)  
**Code:** `/var/www/html/crm_sale` (Laravel 11, project riêng — không nằm trong LCMS)  
**Cập nhật:** 18/09/2026

Bản sao tài liệu cũng nằm tại `/var/www/html/lcms/docs/mô tả hệ thống CRM.md` (cùng nội dung với các tài liệu LCMS liên quan).

---

## 1. Mô tả yêu cầu của hệ thống CRM

CRM là nơi **team Sale / Telesale làm việc hàng ngày**: nhận lead → gọi điện / chăm sóc → đẩy pipeline → chốt đơn → theo dõi đã giao hàng (vào lớp live hoặc cấp quyền sản phẩm khác).

CRM **không** thay Catalog, Payment hay LCMS.

| Hệ thống | Việc làm | Việc không làm |
|---|---|---|
| **CRM** (project này) | Lead, UTM, pipeline, gọi điện, tạo đơn, điều phối fulfill | Config gói, cổng thanh toán, quản lý buổi live |
| **Catalog** (hệ thống khác) | Gói bán, giá, combo; API danh sách + chi tiết gói | Pipeline lead, xếp lớp thật |
| **Payment** (hệ thống khác) | Giao dịch online (VNPAY, Momo, bank…) | Enroll học sinh |
| **LCMS** | Chương trình, lớp live, ENROLLED / WAITING_ENROLL | Lead, UTM, giá bán, đơn thương mại |
| **Phòng luyện / sản phẩm khác** | Cấp quyền / giữ chỗ theo họ | Lớp live LCMS |

Gói bán **đa loại**. Một đơn có thể gồm lớp live (fulfill LCMS), phòng luyện (hệ thống khác), hoặc combo (tách item, gọi đúng hệ thống đích).

```
Lead / UTM / pipeline     ← CRM
        ↓
Chọn gói (API Catalog)
        ↓
Tạo đơn → thanh toán      ← CRM + Payment
        ↓
Đơn Activated
        ↓
Fulfillment Router (CRM)
   ├── live_class  → LCMS
   ├── practice    → hệ thống phòng luyện
   └── combo       → tách item, gọi từng hệ thống
```

**Không enroll lúc tạo đơn nháp.** Chỉ fulfill khi đơn **Activated** (online: Payment báo đã thu; offline: sale xác nhận đã thu).

### Đối tượng dùng

- **Admin:** xem toàn bộ lead, phân sale, CRUD user, reset mật khẩu.
- **Sale:** chỉ thấy / xử lý lead được phân cho mình; tự tạo lead thì owner = chính mình.

Đăng nhập bằng **username + mật khẩu** (không dùng SSO portal học sinh).

---

## 2. Các chức năng cần của CRM

### 2.1. Tài khoản và phân quyền

- Đăng nhập username / mật khẩu; tài khoản có thể tắt.
- Role `admin` / `sale`.
- Admin CRUD user, reset về mật khẩu mặc định.
- Sale không xem lead của người khác.

### 2.2. Lead và nguồn

- Tạo lead: form CRM, import CSV, API intake từ landing / ads / website.
- Bắt buộc có SĐT hoặc email. Nguồn bắt buộc khi nhập tay (Facebook Ads, Landing Page, Hotline, TikTok Ads, Form Website, Zalo, Walk-in, Referral…).
- Mã lead dạng `LD-{năm}{id}` (ví dụ `LD-20261`).
- Gán sale phụ trách (owner). Lead từ API mặc định **chưa phân**.
- Lead chưa bắt buộc có `sso_id` — gắn khi đã có tài khoản / lúc chốt đơn.

### 2.3. UTM / lần chạm (touch)

- Ghi nhiều lần chạm, **không ghi đè** một dòng UTM.
- Giữ **first-touch** (lần đầu) và **last-touch** (lần gần nhất).
- Lưu: `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`, landing, referrer, `gclid`, `fbclid`.
- Khi chốt đơn: copy UTM / nguồn sang đơn (để báo cáo doanh thu không lệch kênh) — **chưa làm**.

### 2.4. Pipeline (stage lead)

Pipeline cấu hình được, không hardcode “Level 1/2/3”. Hiện tại:

`Mới → Đã liên hệ → Đủ điều kiện → Đang tư vấn → Đã tạo đơn → Không chốt`

- Một lead một stage hiện tại.
- Mỗi lần chuyển: ghi lịch sử (ai, lúc nào, lý do).
- Rule: thiếu SĐT không sang “Đang tư vấn”.
- Các stage sau (Đã kích hoạt / Đã giao hàng) phụ thuộc đơn + fulfill, sale **không bấm tay** trạng thái học tập.

### 2.5. Telesale — gọi điện

Đây là màn hình làm việc chính của sale.

- Danh sách bàn gọi: checkbox, mã lead, khách, SĐT, nguồn, trạng thái cuộc gọi màu, lần cuối gọi, nút **GỌI**.
- Tab nhanh:
  - **Lead mới** — chưa gọi
  - **Hẹn gọi lại** — có lịch callback từ hôm nay trở đi
  - **Chăm sóc lại** — đã gọi nhưng cần follow (tắt máy, máy bận, quá hạn hẹn)
- Kết quả gọi: Chưa gọi, Tắt máy, Máy bận, Sai số, Hẹn gọi lại, Quan tâm (Báo giá), Không quan tâm.
- Nút GỌI mở `tel:` + form ghi kết quả / hẹn giờ.
- Tìm theo tên, SĐT, mã lead; lọc nguồn / stage / sale / UTM campaign.
- Import CSV (`name, phone, email, source_code, note`).

### 2.6. Gói bán (chỉ đọc Catalog) — chưa làm

CRM không config gói. Catalog cung cấp API, sale chọn trên CRM.

- `GET` danh sách / chi tiết gói: id, code, name, type, giá, `items[]`.
- `type`: `live_class` | `practice_room` | `combo`.
- Gắn sản phẩm quan tâm lên lead trước khi chốt (field `interested_product` đã có trên lead).

### 2.7. Đơn hàng — chưa làm

- Tạo đơn gắn gói Catalog; **snapshot** gói lúc bán (Catalog đổi sau không sửa đơn cũ).
- Kênh online / offline.
- Trạng thái: `draft` → `pending_payment` → `activated` → `fulfilled` | `partial` | `cancelled`.
- Online: gọi Payment, webhook `paid` → Activated.
- Offline: sale xác nhận đã thu → Activated.
- Đơn chưa Activated **không** gọi LCMS / phòng luyện.

### 2.8. Điều phối fulfill sau Activated — chưa làm

CRM là **router**, không tự enroll trong DB LCMS.

| Item | Hệ thống | Kết quả |
|---|---|---|
| Lớp live đã mở | LCMS `POST /fulfillments` | `ENROLLED` |
| Lớp live chưa mở | LCMS cùng API | `WAITING_ENROLL` |
| Chỉ chương trình | LCMS | Giữ chỗ theo `curriculum_id` |
| Phòng luyện | API phòng luyện | Cấp quyền / giữ chỗ |

Combo: tách item, gọi từng `target_system`. Một nhánh lỗi → đơn `partial`, retry từng dòng. Sale theo dõi enrolled / waiting / failed — không nhập tay.

### 2.9. Báo cáo — chưa làm (mới có dashboard đếm)

- Lead theo nguồn / UTM / campaign.
- Phễu theo stage; tỷ lệ lead → đơn.
- Đơn theo gói, theo loại sản phẩm.
- Số HS đã vào lớp vs đang giữ chỗ (từ fulfill).

### 2.10. CRM không làm

- Config gói, giá, combo (Catalog).
- Xử lý cổng thanh toán (Payment).
- Quản lý chương trình, buổi live, điểm danh, giáo viên (LCMS).
- Cấp quyền phòng luyện.

---

## 3. Hiện tại đã làm được gì

Bước 1 đang xong: **nền tảng + phễu lead + bàn telesale**. Chưa tới đơn hàng / Catalog / Payment / fulfill.

### 3.1. Hạ tầng

- Laravel 11 tại `/var/www/html/crm_sale`, DB MySQL `crm_sale`.
- Domain `crm.ieltscheckmate.com` (Apache + HTTPS).
- Đăng nhập username / mật khẩu; tắt đăng ký / quên mật khẩu công khai.
- Theme telesale đồng bộ: header (CRM TELESALE, search, chuông, user), footer, dashboard, lead, user, login.

### 3.2. User và quyền

| Việc | Trạng thái |
|---|---|
| Role admin / sale, user active/tắt | Đã làm |
| Admin CRUD user | Đã làm |
| Reset mật khẩu về mặc định (`CrmSale@2026`) | Đã làm |
| Sale chỉ thấy lead được phân | Đã làm |
| Tài khoản tự sửa profile / đổi mật khẩu | Đã làm |

User seed: `admin` (Admin), `sale` (Nguyễn Văn A).

### 3.3. Lead — CRUD và phễu

| Việc | Trạng thái |
|---|---|
| Tạo / sửa / xem / xóa lead (admin xóa) | Đã làm |
| Mã lead `LD-{năm}{id}` | Đã làm |
| Nguồn (website, landing, Facebook Ads, Google Ads, TikTok Ads, Zalo, Hotline, Walk-in, Referral…) | Đã làm |
| Pipeline stage + lịch sử chuyển | Đã làm |
| Rule: thiếu SĐT không sang “Đang tư vấn” | Đã làm |
| Gán / bỏ gán sale (admin) | Đã làm |
| Ghi chú / hoạt động (note, call, chat, meeting) | Đã làm |
| Sản phẩm quan tâm (`live_class` / `practice_room` / `combo`) — chỉ lưu trên lead | Đã làm (chưa nối Catalog) |
| Import CSV | Đã làm |

### 3.4. UTM / intake API

| Việc | Trạng thái |
|---|---|
| `POST /api/v1/leads/intake` (header `X-Api-Key`) | Đã làm |
| First-touch giữ nguyên, return-touch cập nhật last-touch | Đã làm |
| Trùng SĐT / email → không tạo lead mới, ghi lần chạm | Đã làm |
| Lead API vào **chưa phân sale** | Đã làm |

### 3.5. Telesale UI

| Việc | Trạng thái |
|---|---|
| Danh sách bàn gọi (mã, SĐT, nguồn, trạng thái màu, lần gọi, nút GỌI) | Đã làm |
| Tab Lead mới / Hẹn gọi lại / Chăm sóc lại | Đã làm |
| Ghi kết quả cuộc gọi + hẹn callback | Đã làm |
| Search header (tên, SĐT, mã lead) | Đã làm |
| Bộ lọc nâng cao (stage, nguồn, sale, UTM campaign) | Đã làm |
| Chuông: số hẹn hôm nay + lead chưa phân | Đã làm |
| Dashboard đếm tổng / mới / hẹn / recare / chưa phân + phễu stage | Đã làm |
| Phân trang 20/50/100 | Đã làm |

### 3.6. Chưa làm (bước tiếp)

| Nhóm | Việc còn lại |
|---|---|
| Catalog | Đọc API gói, chọn gói trên lead / đơn |
| Đơn hàng | Tạo đơn, snapshot gói, online/offline, trạng thái draft → activated |
| Payment | Tạo giao dịch, nhận webhook `paid` |
| Fulfill | Router Activated → LCMS / phòng luyện; enrolled / waiting / failed |
| Webhook LCMS | `class_completed`, `waiting_ready`, `fulfillment.failed`; place-class |
| Báo cáo | UTM → doanh thu, conversion lead → đơn, HS vào lớp vs giữ chỗ |
| SLA / auto-assign | Phân lead tự động, hạn nhận lead |
| UTM lúc chốt | Copy first/last-touch vào đơn conversion |

**Thứ tự đề xuất tiếp:** đọc Catalog → tạo đơn + Payment → LCMS fulfillment → router combo / phòng luyện → báo cáo.

---

## 4. Tiêu chí “CRM cho sale” xong việc

Sale dùng CRM để:

1. Biết lead vào từ nguồn / UTM nào.
2. Gọi điện, hẹn, chăm sóc trên bàn telesale.
3. Đẩy lead qua các stage.
4. Chọn gói từ Catalog (lớp live, phòng luyện, combo) và tạo đơn.
5. Sau Activated: lớp đã mở thì HS vào lớp; lớp chưa mở thì giữ chỗ; gói khác giao sang hệ thống tương ứng.
6. Theo dõi trên đơn: enrolled / waiting / failed — không nhập tay trạng thái học tập.

Hiện tại **đạt mục 1–3** (phễu lead + telesale). Mục 4–6 chưa làm.
