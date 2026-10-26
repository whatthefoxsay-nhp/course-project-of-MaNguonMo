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
            setInterval(() => this.refreshSeatStatus(), 15000);
        }
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

// Quick Search Modal Alpine Component
Alpine.data('searchModal', () => ({
    isOpen: false,
    query: '',
    results: [],
    isLoading: false,

    open() {
        this.isOpen = true;
        this.$nextTick(() => {
            const input = document.getElementById('global-search-input');
            if (input) input.focus();
        });
    },

    close() {
        this.isOpen = false;
        this.query = '';
        this.results = [];
    },

    handleSearch() {
        if (!this.query.trim()) {
            this.results = [];
            return;
        }
        this.isLoading = true;
        // Mock debounce / fast client search query
        setTimeout(() => {
            this.isLoading = false;
        }, 200);
    }
}));

// Countdown timer component for cart
// Bình luận sự kiện: gửi / xóa không tải lại trang
Alpine.data('eventComments', (config = {}) => ({
    comments: config.comments || [],
    newComment: '',
    isPosting: false,
    error: '',

    async submit() {
        const content = this.newComment.trim();
        if (!content || this.isPosting) return;

        if (!config.canComment) {
            window.location.href = config.loginUrl;
            return;
        }

        this.isPosting = true;
        this.error = '';

        try {
            const response = await fetch(config.storeUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ content }),
            });
            const data = await response.json();

            if (!response.ok) {
                this.error = data.errors?.content?.[0] || data.message || 'Không thể gửi bình luận.';
                return;
            }

            this.comments.unshift(data.comment);
            this.newComment = '';
        } catch (error) {
            this.error = 'Mất kết nối tới máy chủ, vui lòng thử lại.';
        } finally {
            this.isPosting = false;
        }
    },

    async remove(comment) {
        if (!confirm('Xóa bình luận này?')) return;

        const response = await fetch(config.destroyUrl.replace('__ID__', comment.id), {
            method: 'DELETE',
            headers: jsonHeaders(),
        });

        if (response.ok) {
            this.comments = this.comments.filter(item => item.id !== comment.id);
        } else {
            this.error = 'Không thể xóa bình luận này.';
        }
    },
}));

Alpine.data('countdownTimer', (initialMinutes = 10, initialSeconds = null) => ({
    totalSeconds: initialSeconds ?? initialMinutes * 60,
    timerInterval: null,
    expired: false,

    init() {
        this.timerInterval = setInterval(() => {
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
    selectedMethod: 'vietqr', // 'vietqr' | 'momo' | 'zalopay' | 'card'
    selectedBank: 'TCB', // 'TCB' | 'VCB' | 'MB'
    orderCode: 'TBX-' + Math.floor(100000 + Math.random() * 900000),
    totalAmount: config.totalAmount || 500000,
    items: config.items || [],
    buyerInfo: {
        fullName: config.userName || '',
        phone: config.userPhone || '',
        email: config.userEmail || '',
        idCard: '',
        city: '',
        address: '',
        notes: '',
    },
    banks: {
        TCB: {
            code: 'TCB',
            shortName: 'Techcombank',
            fullName: 'Ngân hàng TMCP Kỹ Thương Việt Nam',
            accountNo: '1903 8888 6688',
            accountHolder: 'CONG TY CP TICKETBOX VIETNAM',
            color: '#CC0000',
            logoBg: 'bg-red-600',
        },
        VCB: {
            code: 'VCB',
            shortName: 'Vietcombank',
            fullName: 'Ngân hàng TMCP Ngoại Thương Việt Nam',
            accountNo: '0071 0008 89999',
            accountHolder: 'CONG TY CP TICKETBOX VIETNAM',
            color: '#005E28',
            logoBg: 'bg-emerald-700',
        },
        MB: {
            code: 'MB',
            shortName: 'MBBank',
            fullName: 'Ngân hàng TMCP Quân Đội',
            accountNo: '8888 9999 6868',
            accountHolder: 'CONG TY CP TICKETBOX VIETNAM',
            color: '#002B66',
            logoBg: 'bg-blue-800',
        }
    },
    momoInfo: {
        merchant: 'TICKETBOX VIETNAM',
        phone: '0988 888 999',
        fee: 'Miễn phí',
    },
    zalopayInfo: {
        merchant: 'TICKETBOX TICKET JSC',
        appId: 'TBX-APP-889',
        fee: 'Miễn phí',
    },
    toastMsg: '',
    showToast: false,
    toastTimer: null,

    get currentBank() {
        return this.banks[this.selectedBank] || this.banks.TCB;
    },

    get vietQrUrl() {
        const bank = this.currentBank;
        const addInfo = encodeURIComponent(this.orderCode);
        const name = encodeURIComponent(bank.accountHolder);
        return `https://img.vietqr.io/image/${bank.code}-${bank.accountNo}-compact2.png?amount=${this.totalAmount}&addInfo=${addInfo}&accountName=${name}`;
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
                body: JSON.stringify({ payment_method: this.selectedMethod }),
            });
            const data = await response.json();

            if (!response.ok) {
                this.notify(data.message || 'Thanh toán thất bại, vui lòng thử lại.');
                return;
            }

            this.orderCode = data.booking_code;
            this.paymentSuccess = true;
            this.step = 'e_ticket';
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.notify(data.message);
        } catch (error) {
            this.notify('Mất kết nối tới máy chủ, vui lòng thử lại.');
        } finally {
            this.isProcessingPayment = false;
        }
    },

    async removeItem(showtimeSeatId) {
        try {
            const response = await fetch(config.removeUrl.replace('__ID__', showtimeSeatId), {
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
        const firstItem = this.items[0] || {};
        const movieTitle = firstItem.movie_title || 'Live Concert TicketBox 2026';
        const showtime = firstItem.showtime_str || '19:30 - 25/10/2026';
        const venue = firstItem.room_name || 'Sân Khấu Sự Kiện';
        const seats = this.items.map(i => (i.row_label ? i.row_label + i.seat_number : i.seat_type)).join(', ') || 'Ghế C4, C5';

        const canvas = document.createElement('canvas');
        canvas.width = 1200;
        canvas.height = 680;
        const ctx = canvas.getContext('2d');

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
        ctx.setLineDash([]); // reset

        // Left Stub Notch
        ctx.fillStyle = '#14161D';
        ctx.beginPath();
        ctx.arc(820, 40, 20, 0, Math.PI);
        ctx.fill();
        ctx.beginPath();
        ctx.arc(820, 640, 20, Math.PI, 0);
        ctx.fill();

        // Brand & Title
        ctx.fillStyle = '#C08497';
        ctx.font = 'bold 16px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('TICKETBOX OFFICIAL E-TICKET PASS', 70, 85);

        ctx.fillStyle = '#22C55E';
        ctx.font = 'bold 15px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('● ĐÃ THANH TOÁN 100% (CONFIRMED)', 500, 85);

        // Event Title
        ctx.fillStyle = '#000000';
        ctx.font = '900 28px "Playfair Display", serif';
        const displayTitle = movieTitle.length > 38 ? movieTitle.substring(0, 36) + '...' : movieTitle;
        ctx.fillText(displayTitle, 70, 135);

        // Gold line under title
        ctx.fillStyle = '#D4AF37';
        ctx.fillRect(70, 155, 710, 2);

        // Grid Details Left Side
        const drawField = (label, val, x, y, isBig = false) => {
            ctx.fillStyle = '#6B7280';
            ctx.font = 'bold 13px "Plus Jakarta Sans", sans-serif';
            ctx.fillText(label.toUpperCase(), x, y);

            ctx.fillStyle = '#111827';
            ctx.font = isBig ? '900 20px "Space Grotesk", sans-serif' : 'bold 16px "Plus Jakarta Sans", sans-serif';
            ctx.fillText(val, x, y + 26);
        };

        drawField('Thời Gian Diễn Ra', showtime, 70, 200);
        drawField('Địa Điểm & Sân Khấu', venue, 430, 200);

        drawField('Cổng Vào (Gate)', 'CỔNG A1 (LỐI VIP / SEATED)', 70, 280);
        drawField('Vị Trí Ghế / Hạng Vé', seats, 430, 280, true);

        drawField('Người Sở Hữu Vé', `${this.buyerInfo.fullName} (CCCD: ${this.buyerInfo.idCard || 'Chính chủ'})`, 70, 360);
        drawField('Số Điện Thoại / Email', `${this.buyerInfo.phone} · ${this.buyerInfo.email}`, 430, 360);

        drawField('Mã Đơn Đặt Chỗ', this.orderCode, 70, 440, true);
        drawField('Tổng Tiền Thanh Toán', this.formatCurrency(this.totalAmount), 430, 440, true);

        // Security Notice Box
        ctx.fillStyle = '#F5F5DC';
        ctx.roundRect(70, 520, 710, 90, 14);
        ctx.fill();
        ctx.strokeStyle = '#D8D8A8';
        ctx.lineWidth = 1;
        ctx.stroke();

        ctx.fillStyle = '#3A5A40';
        ctx.font = 'bold 13px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('LƯU Ý CHECK-IN SỰ KIỆN:', 90, 548);
        ctx.fillStyle = '#4B5563';
        ctx.font = '12px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('• Xuất trình mã QR tại cổng kiểm soát vé để quét đổi vòng tay / vào khán phòng.', 90, 572);
        ctx.fillText('• Mỗi vé QR chỉ có giá trị cho 01 lần check-in. Vui lòng bảo mật mã vé cá nhân.', 90, 592);

        // Right Stub (QR Code & Barcode Section)
        ctx.fillStyle = '#FAF9F6';
        ctx.roundRect(845, 65, 290, 550, 16);
        ctx.fill();

        ctx.fillStyle = '#000000';
        ctx.font = '900 18px "Space Grotesk", sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('GATE PASS', 990, 105);

        ctx.fillStyle = '#6B7280';
        ctx.font = '12px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('SCAN TO ENTER', 990, 125);

        // Simulated QR Code Frame on Stub
        ctx.fillStyle = '#FFFFFF';
        ctx.roundRect(890, 145, 200, 200, 12);
        ctx.fill();
        ctx.strokeStyle = '#D4AF37';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Draw Stylized QR Grid pattern
        ctx.fillStyle = '#000000';
        // 3 Corner Eyes
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

        // Random matrix dots
        for (let r = 0; r < 7; r++) {
            for (let c = 0; c < 7; c++) {
                if ((r < 3 && c < 3) || (r < 3 && c > 3) || (r > 3 && c < 3)) continue;
                if ((r + c * 3 + this.orderCode.charCodeAt(r % 5)) % 2 === 0) {
                    ctx.fillRect(960 + c * 9, 215 + r * 9, 7, 7);
                }
            }
        }

        // Center TicketBox Logo on QR
        ctx.fillStyle = '#C08497';
        ctx.fillRect(972, 227, 36, 36);
        ctx.fillStyle = '#FFFFFF';
        ctx.font = '900 12px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('TBX', 990, 250);

        // Booking Code
        ctx.fillStyle = '#000000';
        ctx.font = '900 16px "Space Grotesk", sans-serif';
        ctx.fillText(this.orderCode, 990, 380);

        // Barcode lines
        ctx.fillStyle = '#111827';
        const startBx = 875;
        for (let i = 0; i < 40; i++) {
            const w = (i % 3 === 0 || i % 7 === 0) ? 4 : 2;
            const gap = i * 6;
            ctx.fillRect(startBx + gap, 410, w, 45);
        }

        ctx.font = 'bold 11px "Space Grotesk", sans-serif';
        ctx.fillStyle = '#9CA3AF';
        ctx.fillText('AUTH-' + Date.now().toString().slice(-8), 990, 475);

        // Brand Stamp
        ctx.fillStyle = '#3A5A40';
        ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('TICKETBOX VERIFIED', 990, 530);

        ctx.fillStyle = '#D4AF37';
        ctx.font = 'bold 11px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('★ VIP ADMISSION ★', 990, 555);

        ctx.textAlign = 'left'; // reset

        // Trigger Download
        const link = document.createElement('a');
        link.download = `Ve-Dien-Tu-${this.orderCode}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();

        this.notify(`Đã lưu vé điện tử ${this.orderCode}.png về thiết bị của bạn!`);
    },

    printTicket() {
        window.print();
    }
}));

// Global Ticket PNG Downloader Helper
window.downloadTicketPNG = function(bookingData) {
    const movieTitle = bookingData.movieTitle || 'Live Concert TicketBox 2026';
    const showtime = bookingData.showtime || '19:30 - 25/10/2026';
    const venue = bookingData.venue || 'Khán Phòng Sự Kiện';
    const seats = bookingData.seats || 'Ghế VIP';
    const orderCode = bookingData.orderCode || 'TBX-' + Math.floor(100000 + Math.random() * 900000);
    const totalPrice = bookingData.totalPrice ? new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(bookingData.totalPrice) : '500.000 ₫';
    const buyerName = bookingData.buyerName || 'Nguyễn Hải Phong';

    const canvas = document.createElement('canvas');
    canvas.width = 1200;
    canvas.height = 680;
    const ctx = canvas.getContext('2d');

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

    // Left Stub Notch
    ctx.fillStyle = '#14161D';
    ctx.beginPath();
    ctx.arc(820, 40, 20, 0, Math.PI);
    ctx.fill();
    ctx.beginPath();
    ctx.arc(820, 640, 20, Math.PI, 0);
    ctx.fill();

    // Brand & Title
    ctx.fillStyle = '#C08497';
    ctx.font = 'bold 16px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('TICKETBOX OFFICIAL E-TICKET PASS', 70, 85);

    ctx.fillStyle = '#22C55E';
    ctx.font = 'bold 15px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('● ĐÃ THANH TOÁN 100% (CONFIRMED)', 500, 85);

    // Event Title
    ctx.fillStyle = '#000000';
    ctx.font = '900 28px "Playfair Display", serif';
    const displayTitle = movieTitle.length > 38 ? movieTitle.substring(0, 36) + '...' : movieTitle;
    ctx.fillText(displayTitle, 70, 135);

    ctx.fillStyle = '#D4AF37';
    ctx.fillRect(70, 155, 710, 2);

    const drawField = (label, val, x, y, isBig = false) => {
        ctx.fillStyle = '#6B7280';
        ctx.font = 'bold 13px "Plus Jakarta Sans", sans-serif';
        ctx.fillText(label.toUpperCase(), x, y);

        ctx.fillStyle = '#111827';
        ctx.font = isBig ? '900 20px "Space Grotesk", sans-serif' : 'bold 16px "Plus Jakarta Sans", sans-serif';
        ctx.fillText(val, x, y + 26);
    };

    drawField('Thời Gian Diễn Ra', showtime, 70, 200);
    drawField('Địa Điểm & Sân Khấu', venue, 430, 200);

    drawField('Cổng Vào (Gate)', 'CỔNG A1 (LỐI VIP / SEATED)', 70, 280);
    drawField('Vị Trí Ghế / Hạng Vé', seats, 430, 280, true);

    drawField('Người Sở Hữu Vé', buyerName, 70, 360);
    drawField('Trạng Thái Vé', 'HỢP LỆ - ĐÃ KÍCH HOẠT CHECK-IN', 430, 360);

    drawField('Mã Đơn Đặt Chỗ', orderCode, 70, 440, true);
    drawField('Tổng Tiền Thanh Toán', totalPrice, 430, 440, true);

    // Security Notice Box
    ctx.fillStyle = '#F5F5DC';
    ctx.roundRect(70, 520, 710, 90, 14);
    ctx.fill();
    ctx.strokeStyle = '#D8D8A8';
    ctx.lineWidth = 1;
    ctx.stroke();

    ctx.fillStyle = '#3A5A40';
    ctx.font = 'bold 13px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('LƯU Ý CHECK-IN SỰ KIỆN:', 90, 548);
    ctx.fillStyle = '#4B5563';
    ctx.font = '12px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('• Xuất trình mã QR tại cổng kiểm soát vé để quét đổi vòng tay / vào khán phòng.', 90, 572);
    ctx.fillText('• Mỗi vé QR chỉ có giá trị cho 01 lần check-in. Vui lòng bảo mật mã vé cá nhân.', 90, 592);

    // Right Stub
    ctx.fillStyle = '#FAF9F6';
    ctx.roundRect(845, 65, 290, 550, 16);
    ctx.fill();

    ctx.fillStyle = '#000000';
    ctx.font = '900 18px "Space Grotesk", sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('GATE PASS', 990, 105);

    ctx.fillStyle = '#6B7280';
    ctx.font = '12px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('SCAN TO ENTER', 990, 125);

    ctx.fillStyle = '#FFFFFF';
    ctx.roundRect(890, 145, 200, 200, 12);
    ctx.fill();
    ctx.strokeStyle = '#D4AF37';
    ctx.lineWidth = 2;
    ctx.stroke();

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

    for (let r = 0; r < 7; r++) {
        for (let c = 0; c < 7; c++) {
            if ((r < 3 && c < 3) || (r < 3 && c > 3) || (r > 3 && c < 3)) continue;
            if ((r + c * 3 + orderCode.charCodeAt(r % 5)) % 2 === 0) {
                ctx.fillRect(960 + c * 9, 215 + r * 9, 7, 7);
            }
        }
    }

    ctx.fillStyle = '#C08497';
    ctx.fillRect(972, 227, 36, 36);
    ctx.fillStyle = '#FFFFFF';
    ctx.font = '900 12px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('TBX', 990, 250);

    ctx.fillStyle = '#000000';
    ctx.font = '900 16px "Space Grotesk", sans-serif';
    ctx.fillText(orderCode, 990, 380);

    ctx.fillStyle = '#111827';
    const startBx = 875;
    for (let i = 0; i < 40; i++) {
        const w = (i % 3 === 0 || i % 7 === 0) ? 4 : 2;
        const gap = i * 6;
        ctx.fillRect(startBx + gap, 410, w, 45);
    }

    ctx.font = 'bold 11px "Space Grotesk", sans-serif';
    ctx.fillStyle = '#9CA3AF';
    ctx.fillText('AUTH-' + Date.now().toString().slice(-8), 990, 475);

    ctx.fillStyle = '#3A5A40';
    ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('TICKETBOX VERIFIED', 990, 530);

    ctx.fillStyle = '#D4AF37';
    ctx.font = 'bold 11px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('★ VIP ADMISSION ★', 990, 555);

    ctx.textAlign = 'left';

    const link = document.createElement('a');
    link.download = `Ve-Dien-Tu-${orderCode}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
};

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
