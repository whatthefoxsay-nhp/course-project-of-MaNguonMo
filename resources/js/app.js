import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Header chuẩn cho request AJAX tới Laravel (CSRF + nhận JSON)
const jsonHeaders = () => ({
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
});

// Sơ đồ chọn chỗ theo khu: ghế đánh số / ghế quanh bàn (chọn từng ghế) + khu bán theo số lượng
Alpine.data('seatBookingManager', (config = {}) => ({
    maxTickets: 8,
    zoneMeta: config.zones || {},      // { zoneId: { name, price, kind, color, available } }
    selectedSeats: [],                 // [{ id, code, zoneId, zoneName, price, color }]
    zoneQuantities: {},                // { zoneId: số vé }
    activeZoneFilter: 'all',
    hoveredSeat: null,
    hoverTimeout: null,
    zoomScale: 1,
    notificationMessage: '',
    notificationType: 'info',
    showToast: false,
    toastTimeout: null,
    isSubmitting: false,
    unavailableIds: [],

    init() {
        Object.keys(this.zoneMeta).forEach(id => { this.zoneQuantities[id] = 0; });
        if (config.pollStatus && config.statusUrl) {
            // Chỉ poll khi tab đang hiển thị; quay lại tab thì cập nhật ngay.
            this._statusPollTimer = setInterval(() => {
                if (document.visibilityState === 'visible') this.refreshSeatStatus();
            }, 15000);
            this._onVisibilityChange = () => {
                if (document.visibilityState === 'visible') this.refreshSeatStatus();
            };
            document.addEventListener('visibilitychange', this._onVisibilityChange);
        }
    },

    destroy() {
        if (this._statusPollTimer) clearInterval(this._statusPollTimer);
        if (this._onVisibilityChange) document.removeEventListener('visibilitychange', this._onVisibilityChange);
    },

    // ----- Ghế chọn trên sơ đồ -----
    toggleSeat(seat) {
        if (seat.status === 'booked') {
            this.notify(`Ghế ${seat.code} đã được bán. Vui lòng chọn ghế khác!`, 'error');
            return;
        }
        if (seat.status === 'held' || this.unavailableIds.includes(seat.id)) {
            this.notify(`Ghế ${seat.code} đang có người giữ chỗ.`, 'warning');
            return;
        }

        const index = this.selectedSeats.findIndex(s => s.id === seat.id);
        if (index > -1) {
            this.selectedSeats.splice(index, 1);
            this.notify(`Đã bỏ chọn ghế ${seat.code}`, 'info');
            return;
        }
        if (this.totalTicketCount >= this.maxTickets) {
            this.notify(`Bạn chỉ có thể chọn tối đa ${this.maxTickets} vé cho mỗi lượt đặt.`, 'warning');
            return;
        }
        this.selectedSeats.push(seat);
        this.notify(`Đã chọn ${seat.code} (${seat.zoneName}) - ${this.formatCurrency(seat.price)}`, 'success');
    },

    // Chọn / bỏ cả bàn (chỉ các ghế còn trống)
    toggleTable(seats) {
        const free = seats.filter(s => s.status === 'available' && !this.unavailableIds.includes(s.id));
        if (free.length === 0) {
            this.notify('Bàn này đã hết ghế trống.', 'warning');
            return;
        }
        if (free.every(s => this.isSeatSelected(s.id))) {
            this.selectedSeats = this.selectedSeats.filter(s => !free.some(f => f.id === s.id));
            this.notify('Đã bỏ chọn cả bàn.', 'info');
            return;
        }
        const toAdd = free.filter(s => !this.isSeatSelected(s.id));
        const room = this.maxTickets - this.totalTicketCount;
        if (toAdd.length > room) {
            this.notify(`Mỗi lượt đặt tối đa ${this.maxTickets} vé — chỉ thêm được ${room} ghế nữa.`, 'warning');
            return;
        }
        this.selectedSeats.push(...toAdd);
        this.notify(`Đã chọn ${toAdd.length} ghế của bàn.`, 'success');
    },

    isSeatSelected(seatId) {
        return this.selectedSeats.some(s => s.id === seatId);
    },

    removeSeat(seatId) {
        this.selectedSeats = this.selectedSeats.filter(s => s.id !== seatId);
    },

    // ----- Khu bán theo số lượng -----
    incrementZone(zoneId) {
        const zone = this.zoneMeta[zoneId];
        if (!zone) return;
        if (this.totalTicketCount >= this.maxTickets) {
            this.notify(`Bạn chỉ có thể chọn tối đa ${this.maxTickets} vé cho mỗi lượt đặt.`, 'warning');
            return;
        }
        if (this.zoneCount(zoneId) >= zone.available) {
            this.notify(`Khu ${zone.name} chỉ còn ${zone.available} chỗ.`, 'warning');
            return;
        }
        this.zoneQuantities[zoneId] = this.zoneCount(zoneId) + 1;
        this.notify(`Đã thêm 1 vé ${zone.name} (${this.formatCurrency(zone.price)})`, 'success');
    },

    decrementZone(zoneId) {
        if (this.zoneCount(zoneId) > 0) {
            this.zoneQuantities[zoneId]--;
        }
    },

    zoneCount(zoneId) {
        return this.zoneQuantities[zoneId] || 0;
    },

    clearAll() {
        this.selectedSeats = [];
        Object.keys(this.zoneQuantities).forEach(id => { this.zoneQuantities[id] = 0; });
        this.notify('Đã xóa toàn bộ vé đang chọn.', 'info');
    },

    // ----- Hiển thị -----
    setZoneFilter(code) {
        this.activeZoneFilter = code;
    },

    isZoneDimmed(code) {
        return this.activeZoneFilter !== 'all' && this.activeZoneFilter !== code;
    },

    setHoveredSeat(seatData) {
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
            this.hoverTimeout = null;
        }
        this.hoveredSeat = seatData;
    },

    clearHoveredSeat() {
        if (this.hoverTimeout) clearTimeout(this.hoverTimeout);
        this.hoverTimeout = setTimeout(() => { this.hoveredSeat = null; }, 120);
    },

    zoomIn() {
        if (this.zoomScale < 1.4) this.zoomScale = parseFloat((this.zoomScale + 0.1).toFixed(1));
    },

    zoomOut() {
        if (this.zoomScale > 0.6) this.zoomScale = parseFloat((this.zoomScale - 0.1).toFixed(1));
    },

    resetZoom() {
        this.zoomScale = 1;
    },

    get zonePills() {
        return Object.entries(this.zoneQuantities)
            .filter(([, count]) => count > 0)
            .map(([id, count]) => ({ id, count, name: this.zoneMeta[id]?.name ?? '', price: (this.zoneMeta[id]?.price ?? 0) * count }));
    },

    get totalSeatPrice() {
        return this.selectedSeats.reduce((sum, seat) => sum + seat.price, 0);
    },

    get totalZonePrice() {
        return Object.entries(this.zoneQuantities)
            .reduce((sum, [id, count]) => sum + count * (this.zoneMeta[id]?.price ?? 0), 0);
    },

    get totalPrice() {
        return this.totalSeatPrice + this.totalZonePrice;
    },

    get totalTicketCount() {
        return this.selectedSeats.length + Object.values(this.zoneQuantities).reduce((a, b) => a + b, 0);
    },

    get formattedTotalPrice() {
        return this.formatCurrency(this.totalPrice);
    },

    formatCurrency(val) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
    },

    // Đồng bộ trạng thái ghế mỗi 15 giây (spec: AJAX polling, không realtime)
    async refreshSeatStatus() {
        try {
            const response = await fetch(config.statusUrl, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const { unavailable } = await response.json();
            this.unavailableIds = unavailable;

            const lost = this.selectedSeats.filter(seat => unavailable.includes(seat.id));
            if (lost.length > 0) {
                this.selectedSeats = this.selectedSeats.filter(seat => !unavailable.includes(seat.id));
                this.notify(`Ghế ${lost.map(seat => seat.code).join(', ')} vừa được người khác giữ.`, 'warning');
            }
        } catch (error) {
            // mất mạng tạm thời: bỏ qua, lần sau thử lại
        }
    },

    // Gửi ghế + số lượng theo khu lên server để giữ chỗ 10 phút
    async confirmHold() {
        if (this.totalTicketCount === 0 || this.isSubmitting) return;
        this.isSubmitting = true;

        const zones = {};
        Object.entries(this.zoneQuantities).forEach(([id, count]) => {
            if (count > 0) zones[id] = count;
        });

        try {
            const response = await fetch(config.holdUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ seat_ids: this.selectedSeats.map(seat => seat.id), zones }),
            });

            if (response.status === 401) {
                window.location.href = config.loginUrl;
                return;
            }

            const data = await response.json();
            if (!response.ok) {
                this.notify(data.message || 'Không thể giữ chỗ, vui lòng thử lại.', 'error');
                if (config.pollStatus) this.refreshSeatStatus();
                return;
            }

            window.location.href = data.redirect;
        } catch (error) {
            this.notify('Mất kết nối tới máy chủ, vui lòng thử lại.', 'error');
        } finally {
            this.isSubmitting = false;
        }
    },

    notify(msg, type = 'info') {
        this.notificationMessage = msg;
        this.notificationType = type;
        this.showToast = true;
        if (this.toastTimeout) clearTimeout(this.toastTimeout);
        this.toastTimeout = setTimeout(() => { this.showToast = false; }, 3200);
    }
}));

// Bình luận sự kiện: tải thêm / gửi / xóa không tải lại trang.
// Nội dung người dùng luôn render bằng x-text trong view (không x-html) để tránh XSS.
Alpine.data('eventComments', (config = {}) => ({
    comments: config.comments || [],
    total: config.total || 0,
    nextPageUrl: config.nextPageUrl || null,
    newComment: '',
    isPosting: false,
    isLoadingMore: false,
    error: '',

    commentError(response, data, fallback) {
        if (response.status === 401) return 'Phiên đăng nhập đã hết, vui lòng đăng nhập lại.';
        if (response.status === 419) return 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.';
        if (response.status === 429) return 'Bạn thao tác quá nhanh, vui lòng thử lại sau ít phút.';
        return data?.errors?.content?.[0] || data?.message || fallback;
    },

    async loadMore() {
        if (!this.nextPageUrl || this.isLoadingMore) return;
        this.isLoadingMore = true;
        this.error = '';

        try {
            const response = await fetch(this.nextPageUrl, { headers: { 'Accept': 'application/json' } });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                this.error = this.commentError(response, data, 'Không thể tải thêm bình luận.');
                return;
            }

            const known = new Set(this.comments.map(item => item.id));
            this.comments.push(...data.data.filter(item => !known.has(item.id)));
            this.nextPageUrl = data.next_page_url;
            this.total = data.total;
        } catch (error) {
            this.error = 'Mất kết nối tới máy chủ, vui lòng thử lại.';
        } finally {
            this.isLoadingMore = false;
        }
    },

    async submit() {
        if (!config.canComment) {
            window.location.href = config.loginUrl;
            return;
        }

        const content = this.newComment.trim();
        if (!content || this.isPosting) return;

        this.isPosting = true;
        this.error = '';

        try {
            const response = await fetch(config.storeUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ content }),
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                this.error = this.commentError(response, data, 'Không thể gửi bình luận.');
                return;
            }

            this.comments.unshift(data.comment);
            this.total++;
            this.newComment = '';
        } catch (error) {
            this.error = 'Mất kết nối tới máy chủ, vui lòng thử lại.';
        } finally {
            this.isPosting = false;
        }
    },

    async remove(comment) {
        if (!confirm('Xóa bình luận này?')) return;
        this.error = '';

        try {
            const response = await fetch(config.destroyUrl.replace('__ID__', comment.id), {
                method: 'DELETE',
                headers: jsonHeaders(),
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                this.error = this.commentError(response, data, 'Không thể xóa bình luận này.');
                return;
            }

            this.comments = this.comments.filter(item => item.id !== comment.id);
            this.total = Math.max(0, this.total - 1);
        } catch (error) {
            this.error = 'Mất kết nối tới máy chủ, vui lòng thử lại.';
        }
    },
}));

Alpine.data('searchModal', () => ({
    isOpen: false,
    query: '',
    results: [],
    isLoading: false,
    hasError: false,
    popular: [],
    popularLoaded: false,
    searchTimer: null,
    searchController: null,

    suggestUrl() {
        return document.getElementById('global-search-input')?.dataset.suggestUrl;
    },

    open() {
        this.isOpen = true;
        this.$nextTick(() => {
            const input = document.getElementById('global-search-input');
            if (input) input.focus();
        });
        this.loadPopular();
    },

    close() {
        this.isOpen = false;
        this.query = '';
        this.results = [];
        this.isLoading = false;
        this.hasError = false;
        clearTimeout(this.searchTimer);
        this.searchController?.abort();
        this.searchController = null;
    },

    // Sự kiện nổi bật hiện khi chưa gõ (tải 1 lần khi mở modal lần đầu)
    async loadPopular() {
        if (this.popularLoaded || !this.suggestUrl()) return;
        this.popularLoaded = true;
        try {
            const response = await fetch(this.suggestUrl(), { headers: { 'Accept': 'application/json' } });
            if (response.ok) {
                this.popular = (await response.json()).popular || [];
            }
        } catch (error) {
            this.popularLoaded = false;
        }
    },

    // Gợi ý tìm kiếm AJAX, debounce 250ms; huỷ request cũ khi gõ tiếp
    handleSearch() {
        clearTimeout(this.searchTimer);
        this.searchController?.abort();
        this.searchController = null;
        const query = this.query.trim();
        this.hasError = false;

        if (query.length < 2) {
            this.results = [];
            this.isLoading = false;
            return;
        }

        this.isLoading = true;
        this.searchTimer = setTimeout(async () => {
            const controller = new AbortController();
            this.searchController = controller;
            try {
                const response = await fetch(`${this.suggestUrl()}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal,
                });
                const data = await response.json().catch(() => ({}));
                this.results = response.ok ? (data.results || []) : [];
                this.hasError = !response.ok;
            } catch (error) {
                if (error.name === 'AbortError') return;
                this.results = [];
                this.hasError = true;
            } finally {
                if (this.searchController === controller) {
                    this.isLoading = false;
                    this.searchController = null;
                }
            }
        }, 250);
    }
}));

// Countdown timer component for cart
Alpine.data('countdownTimer', (initialMinutes = 10, initialSeconds = null) => ({
    totalSeconds: initialSeconds ?? initialMinutes * 60,
    timerInterval: null,
    expired: false,
    stopped: false,

    init() {
        this.timerInterval = setInterval(() => {
            if (this.stopped) return;
            if (this.totalSeconds > 0) {
                this.totalSeconds--;
                return;
            }
            this.expired = true;
            clearInterval(this.timerInterval);
            // Giỏ vé thật: hết hạn giữ ghế thì tải lại để server trả giỏ trống
            if (initialSeconds !== null && initialSeconds > 0) {
                window.location.reload();
            }
        }, 1000);
    },

    // Gọi khi thanh toán xong (sự kiện "checkout-completed"): dừng hẳn, không reload trang vé điện tử.
    stop() {
        this.stopped = true;
        clearInterval(this.timerInterval);
    },

    get minutes() {
        return String(Math.floor(this.totalSeconds / 60)).padStart(2, '0');
    },

    get seconds() {
        return String(this.totalSeconds % 60).padStart(2, '0');
    }
}));

// Ticket Checkout & Payment Flow Alpine Component
Alpine.data('ticketPaymentManager', (config = {}) => ({
    step: 'cart', // 'cart' | 'payment_qr' | 'e_ticket'
    isProcessingPayment: false,
    paymentSuccess: false,
    selectedMethod: 'vietqr', // 'vietqr' | 'momo' | 'zalopay' | 'card' (đều là giả lập)
    // Mã tham chiếu tạm của giỏ vé (chỉ để hiển thị); mã đơn thật do server trả về sau khi thanh toán.
    cartRef: 'GIO-' + Math.floor(100000 + Math.random() * 900000),
    orderCode: '',
    totalAmount: config.totalAmount || 0,
    items: config.items || [],
    discountCodeInput: '',
    appliedDiscount: null, // { code, discount_amount, total } do server trả về
    discountMessage: '',
    isApplyingDiscount: false,
    paidAmount: null, // số tiền server xác nhận sau khi thanh toán thành công
    buyerInfo: {
        fullName: config.userName || '',
        phone: config.userPhone || '',
        email: config.userEmail || '',
    },
    toastMsg: '',
    showToast: false,
    toastTimer: null,

    // QR MINH HỌA: nội dung là văn bản thường, KHÔNG phải payload VietQR/EMVCo hay ví điện tử,
    // nên quét bằng app ngân hàng sẽ không tạo được lệnh chuyển tiền.
    get demoQrUrl() {
        const text = `DEMO THANH TOAN GIA LAP - ${this.orderCode || this.cartRef}`;
        return 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(text);
    },

    get payableAmount() {
        if (this.paidAmount !== null) return this.paidAmount;
        return this.appliedDiscount ? this.appliedDiscount.total : this.totalAmount;
    },

    async applyDiscount() {
        const code = this.discountCodeInput.trim().toUpperCase();
        if (!code) {
            this.discountMessage = 'Vui lòng nhập mã giảm giá.';
            return;
        }
        if (this.isApplyingDiscount) return;

        this.isApplyingDiscount = true;
        try {
            const response = await fetch(config.discountUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ code }),
            });
            const data = await response.json();
            if (!response.ok) {
                this.appliedDiscount = null;
                this.discountMessage = data.message || 'Không áp dụng được mã giảm giá.';
                return;
            }
            this.appliedDiscount = { code: data.code, discount_amount: data.discount_amount, total: data.total };
            this.discountCodeInput = data.code;
            this.discountMessage = data.message;
        } catch (error) {
            this.discountMessage = 'Mất kết nối tới máy chủ, vui lòng thử lại.';
        } finally {
            this.isApplyingDiscount = false;
        }
    },

    removeDiscount() {
        this.appliedDiscount = null;
        this.discountCodeInput = '';
        this.discountMessage = '';
    },

    notify(msg) {
        this.toastMsg = msg;
        this.showToast = true;
        if (this.toastTimer) clearTimeout(this.toastTimer);
        this.toastTimer = setTimeout(() => {
            this.showToast = false;
        }, 3000);
    },

    copyToClipboard(text, label) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                this.notify(`Đã sao chép ${label}: ${text}`);
            }).catch(() => {
                this.fallbackCopy(text, label);
            });
        } else {
            this.fallbackCopy(text, label);
        }
    },

    fallbackCopy(text, label) {
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        this.notify(`Đã sao chép ${label}: ${text}`);
    },

    proceedToPayment() {
        if (!this.buyerInfo.fullName.trim() || !this.buyerInfo.phone.trim()) {
            this.notify('Vui lòng điền họ tên và số điện thoại người nhận vé!');
            return;
        }
        this.step = 'payment_qr';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    backToCart() {
        this.step = 'cart';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    async simulatePaymentSuccess() {
        if (this.isProcessingPayment || this.paymentSuccess) return;

        this.isProcessingPayment = true;
        this.notify('Đang kiểm tra và xác nhận giao dịch thanh toán...');

        try {
            const response = await fetch(config.checkoutUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({
                    payment_method: this.selectedMethod,
                    buyer_name: this.buyerInfo.fullName.trim(),
                    buyer_phone: this.buyerInfo.phone.trim(),
                    buyer_email: this.buyerInfo.email.trim() || null,
                    discount_code: this.appliedDiscount ? this.appliedDiscount.code : null,
                }),
            });
            const data = await response.json();

            if (!response.ok) {
                this.notify(data.message || 'Thanh toán thất bại, vui lòng thử lại.');
                if (data.errors && data.errors.discount_code) {
                    // Mã không còn hợp lệ lúc thanh toán: bỏ mã, quay lại giỏ để khách xem lại tổng tiền.
                    this.appliedDiscount = null;
                    this.discountMessage = data.message;
                    this.backToCart();
                }
                if (data.errors && (data.errors.buyer_name || data.errors.buyer_phone || data.errors.buyer_email)) {
                    this.backToCart();
                }
                return;
            }

            this.orderCode = data.booking_code;
            this.paidAmount = data.total_price;
            this.paymentSuccess = true;
            window.dispatchEvent(new CustomEvent('checkout-completed'));
            this.step = 'e_ticket';
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.notify(data.message);
        } catch (error) {
            this.notify('Mất kết nối tới máy chủ, vui lòng thử lại.');
        } finally {
            this.isProcessingPayment = false;
        }
    },

    async removeItem(item) {
        try {
            const response = await fetch(config.removeUrl.replace('__ID__', item.id), {
                method: 'DELETE',
                headers: jsonHeaders(),
            });
            const data = await response.json();
            if (!response.ok) {
                this.notify(data.message || 'Không thể bỏ vé này.');
                return;
            }
            window.location.reload();
        } catch (error) {
            this.notify('Mất kết nối tới máy chủ, vui lòng thử lại.');
        }
    },

    formatCurrency(val) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
    },

    downloadTicketAsPNG() {
        const first = this.items[0] || {};
        window.downloadTicketPNG({
            eventTitle: first.movie_title,
            showtime: first.showtime_str,
            venue: first.room_name,
            seats: this.items.map(i => i.label).join(', '),
            orderCode: this.orderCode,
            totalPrice: this.payableAmount,
            discountAmount: this.appliedDiscount ? this.appliedDiscount.discount_amount : 0,
            buyerName: this.buyerInfo.fullName,
            buyerContact: [this.buyerInfo.phone, this.buyerInfo.email].filter(Boolean).join(' · '),
            status: 'confirmed',
            statusLabel: 'Đã xác nhận',
        });

        this.notify(`Đã lưu vé điện tử ${this.orderCode}.png về thiết bị của bạn!`);
    },

    printTicket() {
        window.print();
    }
}));

/**
 * Vẽ vé điện tử ra ảnh PNG và tải về — dùng chung cho trang giỏ vé (sau thanh toán) và lịch sử vé.
 * Chỉ in dữ liệu thật của đơn; trường nào thiếu thì để trống thay vì điền dữ liệu giả.
 *
 * @param {{eventTitle?: string, showtime?: string, venue?: string, seats?: string, orderCode: string,
 *          totalPrice?: number, discountAmount?: number, buyerName?: string, buyerContact?: string,
 *          status?: string, statusLabel?: string, qrSvg?: string}} data
 *        qrSvg: mã QR thật (SVG do server tạo, cùng nội dung với vé PDF). Thiếu thì vẽ khung minh họa.
 */
function drawAndDownloadTicket(data) {
    const formatVnd = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
    const orderCode = data.orderCode || '';
    const status = data.status || 'confirmed';
    const statusLabel = (data.statusLabel || status).toUpperCase();
    const statusColor = status === 'confirmed' ? '#16A34A' : (status === 'cancelled' ? '#DC2626' : '#D97706');

    const canvas = document.createElement('canvas');
    canvas.width = 1200;
    canvas.height = 680;
    const ctx = canvas.getContext('2d');

    // Cắt chữ cho vừa chiều rộng (danh sách ghế / tên sự kiện dài)
    const fit = (text, maxWidth) => {
        let value = String(text ?? '');
        if (ctx.measureText(value).width <= maxWidth) return value;
        while (value.length > 1 && ctx.measureText(value + '…').width > maxWidth) {
            value = value.slice(0, -1);
        }
        return value + '…';
    };

    // Background
    ctx.fillStyle = '#14161D';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Header Gradient Banner
    const grad = ctx.createLinearGradient(0, 0, 1200, 0);
    grad.addColorStop(0, '#C08497');
    grad.addColorStop(0.5, '#D4AF37');
    grad.addColorStop(1, '#3A5A40');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, 1200, 12);

    // Ticket Card Body
    ctx.fillStyle = '#FFFFFF';
    ctx.roundRect(40, 40, 1120, 600, 24);
    ctx.fill();
    ctx.strokeStyle = '#D4AF37';
    ctx.lineWidth = 3;
    ctx.stroke();

    // Stub divider line (dashed)
    ctx.setLineDash([8, 8]);
    ctx.strokeStyle = '#C08497';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(820, 40);
    ctx.lineTo(820, 640);
    ctx.stroke();
    ctx.setLineDash([]);

    // Stub notches
    ctx.fillStyle = '#14161D';
    ctx.beginPath();
    ctx.arc(820, 40, 20, 0, Math.PI);
    ctx.fill();
    ctx.beginPath();
    ctx.arc(820, 640, 20, Math.PI, 0);
    ctx.fill();

    // Heading & status
    ctx.fillStyle = '#C08497';
    ctx.font = 'bold 16px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('VÉ ĐIỆN TỬ · E-TICKET', 70, 85);

    ctx.fillStyle = statusColor;
    ctx.font = 'bold 15px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('● ' + fit(statusLabel, 300), 470, 85);

    // Event Title
    ctx.fillStyle = '#000000';
    ctx.font = '900 28px "Playfair Display", serif';
    ctx.fillText(fit(data.eventTitle || '', 710), 70, 135);

    ctx.fillStyle = '#D4AF37';
    ctx.fillRect(70, 155, 710, 2);

    const drawField = (label, val, x, y, maxWidth, isBig = false) => {
        ctx.fillStyle = '#6B7280';
        ctx.font = 'bold 13px "Plus Jakarta Sans", sans-serif';
        ctx.fillText(label.toUpperCase(), x, y);

        ctx.fillStyle = '#111827';
        ctx.font = isBig ? '900 20px "Space Grotesk", sans-serif' : 'bold 16px "Plus Jakarta Sans", sans-serif';
        ctx.fillText(fit(val || '—', maxWidth), x, y + 26);
    };

    drawField('Thời Gian Diễn Ra', data.showtime, 70, 200, 340);
    drawField('Địa Điểm', data.venue, 430, 200, 350);

    drawField('Vị Trí Ghế / Hạng Vé', data.seats, 70, 280, 710, true);

    drawField('Người Nhận Vé', data.buyerName, 70, 360, 340);
    drawField('Liên Hệ', data.buyerContact, 430, 360, 350);

    drawField('Mã Đặt Vé', orderCode, 70, 440, 340, true);
    const totalText = formatVnd(data.totalPrice) + (data.discountAmount > 0 ? ` (đã giảm ${formatVnd(data.discountAmount)})` : '');
    drawField('Tổng Tiền Thanh Toán', totalText, 430, 440, 350, true);

    // Notice Box
    ctx.fillStyle = '#F5F5DC';
    ctx.roundRect(70, 520, 710, 90, 14);
    ctx.fill();
    ctx.strokeStyle = '#D8D8A8';
    ctx.lineWidth = 1;
    ctx.stroke();

    ctx.fillStyle = '#3A5A40';
    ctx.font = 'bold 13px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('LƯU Ý:', 90, 548);
    ctx.fillStyle = '#4B5563';
    ctx.font = '12px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('• Xuất trình mã đặt vé khi check-in. Không chia sẻ mã vé cho người khác.', 90, 572);
    ctx.fillText('• Vé phát hành từ hệ thống thanh toán giả lập – Đồ án môn Mã nguồn mở, không có giá trị thực tế.', 90, 592);

    // Right Stub
    ctx.fillStyle = '#FAF9F6';
    ctx.roundRect(845, 65, 290, 550, 16);
    ctx.fill();

    ctx.textAlign = 'center';
    ctx.fillStyle = '#000000';
    ctx.font = '900 18px "Space Grotesk", sans-serif';
    ctx.fillText('MÃ VÉ', 990, 105);

    const qrSvg = data.qrSvg || '';

    ctx.fillStyle = '#6B7280';
    ctx.font = '12px "Plus Jakarta Sans", sans-serif';
    ctx.fillText(qrSvg ? 'Quét mã tại cổng check-in' : 'Hình minh họa, không phải mã quét', 990, 125);

    ctx.fillStyle = '#FFFFFF';
    ctx.roundRect(890, 145, 200, 200, 12);
    ctx.fill();
    ctx.strokeStyle = '#D4AF37';
    ctx.lineWidth = 2;
    ctx.stroke();

    // Không có QR thật: vẽ khung minh họa sinh từ mã đặt vé (ghi rõ "không phải mã quét" ở trên)
    if (!qrSvg) {
        ctx.fillStyle = '#000000';
        const drawEye = (ex, ey) => {
            ctx.fillRect(ex, ey, 45, 45);
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(ex + 8, ey + 8, 29, 29);
            ctx.fillStyle = '#000000';
            ctx.fillRect(ex + 15, ey + 15, 15, 15);
        };
        drawEye(905, 160);
        drawEye(1030, 160);
        drawEye(905, 285);

        const codeChars = orderCode || '0';
        for (let r = 0; r < 7; r++) {
            for (let c = 0; c < 7; c++) {
                if ((r < 3 && c < 3) || (r < 3 && c > 3) || (r > 3 && c < 3)) continue;
                if ((r + c * 3 + codeChars.charCodeAt((r + c) % codeChars.length)) % 2 === 0) {
                    ctx.fillRect(960 + c * 9, 215 + r * 9, 7, 7);
                }
            }
        }
    }

    // Booking Code
    ctx.fillStyle = '#000000';
    ctx.font = '900 16px "Space Grotesk", sans-serif';
    ctx.fillText(orderCode, 990, 380);

    // Decorative barcode
    ctx.fillStyle = '#111827';
    for (let i = 0; i < 40; i++) {
        const w = (i % 3 === 0 || i % 7 === 0) ? 4 : 2;
        ctx.fillRect(875 + i * 6, 410, w, 45);
    }

    ctx.fillStyle = statusColor;
    ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
    ctx.fillText(fit(statusLabel, 260), 990, 500);

    ctx.fillStyle = '#3A5A40';
    ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('Đồ án môn Mã nguồn mở', 990, 545);

    ctx.textAlign = 'left';

    const download = () => {
        const link = document.createElement('a');
        link.download = `Ve-Dien-Tu-${orderCode || 've'}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    };

    if (!qrSvg) {
        download();
        return;
    }

    // QR thật: vẽ ảnh SVG từ blob cục bộ (không gọi dịch vụ ngoài), giữ vùng trắng quanh mã để quét ổn định.
    const qrImage = new Image();
    const qrUrl = URL.createObjectURL(new Blob([qrSvg], { type: 'image/svg+xml;charset=utf-8' }));
    qrImage.onload = () => {
        ctx.drawImage(qrImage, 905, 160, 170, 170);
        URL.revokeObjectURL(qrUrl);
        download();
    };
    qrImage.onerror = () => {
        URL.revokeObjectURL(qrUrl);
        console.error('Could not render the ticket QR image.');
        download();
    };
    qrImage.src = qrUrl;
}

window.downloadTicketPNG = drawAndDownloadTicket;

// Admin Auto Seat Map & Ticket Tier Builder Alpine Component
Alpine.data('adminSeatMapBuilder', (config = {}) => ({
    templates: config.templates || {},
    preset: config.preset || 'mega_concert',
    locked: !!config.locked,           // địa điểm đã có suất diễn: chỉ sửa thông tin khu
    zones: [],
    rows: [],
    selectedTool: 'aisle',
    gridRows: 10,
    gridCols: 14,
    hoveredCell: null,
    zoomScale: 1,
    palette: ['#D4AF37', '#E5A93C', '#C08497', '#3A5A40', '#2563EB', '#99334D', '#1F2937', '#0EA5E9', '#7C3AED', '#EA580C'],

    init() {
        this.loadLayout(config.initialLayout || this.templates[this.preset]?.layout || { zones: [], rows: [] });
    },

    loadLayout(layout) {
        const copy = JSON.parse(JSON.stringify(layout));
        this.zones = (copy.zones || []).map(zone => ({ ...zone, perksText: (zone.perks || []).join(', ') }));
        this.rows = copy.rows || [];
        this.selectedTool = this.pickableZones[0]?.code || 'aisle';
    },

    selectPreset(preset) {
        if (this.locked || !this.templates[preset]) return;
        this.preset = preset;
        this.loadLayout(this.templates[preset].layout);
    },

    resetToTemplate() {
        this.selectPreset(this.preset);
    },

    buildCustomGrid() {
        if (this.locked) return;
        const rowCount = Math.max(2, Math.min(26, parseInt(this.gridRows) || 10));
        const colCount = Math.max(4, Math.min(40, parseInt(this.gridCols) || 14));
        const zones = this.templates.custom_grid?.layout?.zones || [];
        const rows = [];
        for (let r = 0; r < rowCount; r++) {
            rows.push({
                label: String.fromCharCode(65 + r),
                cells: Array.from({ length: colCount }, () => ({ zone: r < 2 ? 'VIP' : 'STD' })),
            });
        }
        this.preset = 'custom_grid';
        this.loadLayout({ zones, rows });
    },

    get pickableZones() {
        return this.zones.filter(zone => zone.kind !== 'standing');
    },

    get standingZones() {
        return this.zones.filter(zone => zone.kind === 'standing');
    },

    // ----- Khu vực -----
    addZone(kind) {
        if (this.locked) return;
        let n = this.zones.length + 1;
        while (this.zones.some(zone => zone.code === 'KHU' + n)) n++;
        const level = Math.max(1, ...this.zones.map(zone => parseInt(zone.level) || 1));
        this.zones.push({
            code: 'KHU' + n,
            name: kind === 'standing' ? 'Khu đứng mới' : (kind === 'table' ? 'Khu bàn mới' : 'Khu ghế mới'),
            kind,
            level,
            surcharge: 0,
            color: this.palette[this.zones.length % this.palette.length],
            gate: '',
            description: '',
            perks: [],
            perksText: '',
            capacity: kind === 'standing' ? 100 : null,
        });
        if (kind !== 'standing') this.selectedTool = 'KHU' + n;
    },

    removeZone(code) {
        if (this.locked || this.zones.length <= 1) return;
        this.zones = this.zones.filter(zone => zone.code !== code);
        this.rows.forEach(row => {
            row.cells = row.cells.map(cell => (cell.zone === code ? { aisle: true } : cell));
        });
        if (this.selectedTool === code) this.selectedTool = this.pickableZones[0]?.code || 'aisle';
    },

    zoneColor(code) {
        return this.zones.find(zone => zone.code === code)?.color || '#4B5563';
    },

    zoneName(code) {
        return this.zones.find(zone => zone.code === code)?.name || code;
    },

    // ----- Hàng & ô -----
    paintCell(rowIdx, cellIdx) {
        if (this.locked) return;
        const row = this.rows[rowIdx];
        if (!row) return;
        row.cells[cellIdx] = this.selectedTool === 'aisle' ? { aisle: true } : { zone: this.selectedTool };
    },

    paintRow(rowIdx) {
        if (this.locked) return;
        const row = this.rows[rowIdx];
        if (!row) return;
        row.cells = row.cells.map(() => (this.selectedTool === 'aisle' ? { aisle: true } : { zone: this.selectedTool }));
    },

    defaultCell() {
        const zone = this.selectedTool !== 'aisle' ? this.selectedTool : this.pickableZones[0]?.code;
        return zone ? { zone } : { aisle: true };
    },

    addRow() {
        if (this.locked) return;
        let n = this.rows.length + 1;
        while (this.rows.some(row => row.label.toUpperCase() === 'H' + n)) n++;
        const width = this.rows[this.rows.length - 1]?.cells.length || 10;
        this.rows.push({ label: 'H' + n, cells: Array.from({ length: width }, () => this.defaultCell()) });
    },

    removeRow(rowIdx) {
        if (this.locked) return;
        this.rows.splice(rowIdx, 1);
    },

    addCell(rowIdx) {
        if (this.locked || this.rows[rowIdx].cells.length >= 40) return;
        this.rows[rowIdx].cells.push(this.defaultCell());
    },

    removeCell(rowIdx) {
        if (this.locked || this.rows[rowIdx].cells.length <= 1) return;
        this.rows[rowIdx].cells.pop();
    },

    // Số ghế hiển thị = thứ tự trong hàng, bỏ qua lối đi (giống server)
    seatNumber(rowIdx, cellIdx) {
        return this.rows[rowIdx].cells.slice(0, cellIdx + 1).filter(cell => !cell.aisle).length;
    },

    // ----- Thống kê & dữ liệu gửi lên -----
    get stats() {
        const counts = {};
        this.zones.forEach(zone => { counts[zone.code] = zone.kind === 'standing' ? (parseInt(zone.capacity) || 0) : 0; });
        let aisles = 0;
        this.rows.forEach(row => row.cells.forEach(cell => {
            if (cell.aisle) aisles++;
            else if (counts[cell.zone] !== undefined) counts[cell.zone]++;
        }));
        return { counts, aisles, total: Object.values(counts).reduce((a, b) => a + b, 0) };
    },

    get layoutJson() {
        return JSON.stringify({
            zones: this.zones.map(zone => ({
                code: zone.code,
                name: zone.name,
                kind: zone.kind,
                level: parseInt(zone.level) || 1,
                surcharge: parseInt(zone.surcharge) || 0,
                color: zone.color,
                gate: zone.gate || null,
                description: zone.description || null,
                perks: (zone.perksText || '').split(',').map(perk => perk.trim()).filter(Boolean),
                capacity: zone.kind === 'standing' ? (parseInt(zone.capacity) || 0) : null,
            })),
            rows: this.rows,
        });
    },

    zoomIn() {
        if (this.zoomScale < 1.3) this.zoomScale = parseFloat((this.zoomScale + 0.1).toFixed(1));
    },

    zoomOut() {
        if (this.zoomScale > 0.6) this.zoomScale = parseFloat((this.zoomScale - 0.1).toFixed(1));
    },

    resetZoom() {
        this.zoomScale = 1;
    },

    formatCurrency(val) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
    }
}));

Alpine.start();

// Intersection Observer for Scroll Reveal
document.addEventListener('DOMContentLoaded', () => {
    const observerCallback = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            }
        });
    };

    const observer = new IntersectionObserver(observerCallback, {
        root: null,
        rootMargin: '0px 0px -40px 0px',
        threshold: 0.1
    });

    document.querySelectorAll('.reveal-on-scroll').forEach(el => {
        observer.observe(el);
    });
});
