import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Header chuẩn cho request AJAX tới Laravel (CSRF + nhận JSON)
const jsonHeaders = () => ({
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
});

// Global Multi-Tier Seat Map & Ticket Booking Alpine Component
Alpine.data('seatBookingManager', (config = {}) => ({
    selectedSeats: [],
    tierQuantities: {},
    tierMeta: {},
    basePrice: config.basePrice || 180000,
    activeZoneFilter: 'all',
    hoveredSeat: null,
    zoomScale: 1,
    standingCount: 0,
    standingPrice: config.standingPrice || 0,
    notificationMessage: '',
    notificationType: 'info',
    showToast: false,
    toastTimeout: null,
    isSubmitting: false,
    unavailableIds: [],

    init() {
        if (config.tiers) {
            Object.keys(config.tiers).forEach(k => {
                this.tierQuantities[k] = 0;
                this.tierMeta[k] = config.tiers[k];
            });
        }
        if (config.pollStatus && config.statusUrl) {
            setInterval(() => this.refreshSeatStatus(), 15000);
        }
    },

    // Individual Seat Toggle for Seated Concerts
    toggleSeat(seatId, seatNumber, rowLabel, type, typeName, status, price, perks = []) {
        if (status === 'booked') {
            this.notify(`Ghế ${rowLabel}${seatNumber} đã được bán. Vui lòng chọn ghế khác!`, 'error');
            return;
        }

        if (status === 'held') {
            this.notify(`Ghế ${rowLabel}${seatNumber} đang có người giữ chỗ trong 10 phút.`, 'warning');
            return;
        }

        if (this.unavailableIds.includes(seatId)) {
            this.notify(`Ghế ${rowLabel}${seatNumber} vừa có người giữ. Vui lòng chọn ghế khác!`, 'warning');
            return;
        }

        const index = this.selectedSeats.findIndex(s => s.id === seatId);
        if (index > -1) {
            this.selectedSeats.splice(index, 1);
            this.notify(`Đã bỏ chọn ghế ${rowLabel}${seatNumber}`, 'info');
        } else {
            if (this.totalTicketCount >= 8) {
                this.notify('Bạn chỉ có thể chọn tối đa 8 vé cho mỗi lượt đặt.', 'warning');
                return;
            }

            const seatPrice = price || this.basePrice;
            this.selectedSeats.push({
                id: seatId,
                number: seatNumber,
                row: rowLabel,
                type: type,
                typeName: typeName || type.toUpperCase(),
                price: seatPrice,
                perks: perks || []
            });
            this.notify(`Đã chọn ${rowLabel}${seatNumber} (${typeName || type.toUpperCase()}) - ${this.formatCurrency(seatPrice)}`, 'success');
        }
    },

    isSeatSelected(seatId) {
        return this.selectedSeats.some(s => s.id === seatId);
    },

    removeSeat(seatId) {
        const index = this.selectedSeats.findIndex(s => s.id === seatId);
        if (index > -1) {
            const seat = this.selectedSeats[index];
            this.selectedSeats.splice(index, 1);
            this.notify(`Đã bỏ chọn ${seat.row}${seat.number}`, 'info');
        }
    },

    // Non-Concert Ticket Tier Quantity Steppers
    incrementTier(tierKey, tierPrice, tierName) {
        if (this.totalTicketCount >= 8) {
            this.notify('Bạn chỉ có thể chọn tối đa 8 vé cho mỗi lượt đặt.', 'warning');
            return;
        }
        if (!this.tierQuantities[tierKey]) {
            this.tierQuantities[tierKey] = 0;
        }
        this.tierQuantities[tierKey]++;
        this.notify(`Đã thêm 1 vé ${tierName} (${this.formatCurrency(tierPrice)})`, 'success');
    },

    decrementTier(tierKey) {
        if (this.tierQuantities[tierKey] && this.tierQuantities[tierKey] > 0) {
            this.tierQuantities[tierKey]--;
            this.notify('Đã giảm số lượng vé', 'info');
        }
    },

    getTierCount(tierKey) {
        return this.tierQuantities[tierKey] || 0;
    },

    clearAll() {
        this.selectedSeats = [];
        this.standingCount = 0;
        Object.keys(this.tierQuantities).forEach(k => {
            this.tierQuantities[k] = 0;
        });
        this.notify('Đã xóa toàn bộ vé đang chọn.', 'info');
    },

    addStandingTicket() {
        if (this.totalTicketCount >= 8) {
            this.notify('Bạn chỉ có thể chọn tối đa 8 vé cho mỗi lượt đặt.', 'warning');
            return;
        }
        this.standingCount++;
        this.notify(`Đã thêm 1 vé Khu Đứng GA Fanzone (${this.formatCurrency(this.standingPrice)})`, 'success');
    },

    removeStandingTicket() {
        if (this.standingCount > 0) {
            this.standingCount--;
            this.notify('Đã giảm 1 vé Khu Đứng GA Fanzone', 'info');
        }
    },

    setZoneFilter(zone) {
        this.activeZoneFilter = zone;
        if (zone !== 'all') {
            this.notify(`Đang lọc khu vực: ${zone.toUpperCase()}`, 'info');
        }
    },

    setHoveredSeat(seatData) {
        this.hoveredSeat = seatData;
    },

    clearHoveredSeat() {
        this.hoveredSeat = null;
    },

    zoomIn() {
        if (this.zoomScale < 1.4) {
            this.zoomScale = parseFloat((this.zoomScale + 0.1).toFixed(1));
        }
    },

    zoomOut() {
        if (this.zoomScale > 0.8) {
            this.zoomScale = parseFloat((this.zoomScale - 0.1).toFixed(1));
        }
    },

    resetZoom() {
        this.zoomScale = 1;
    },

    get totalSeatPrice() {
        return this.selectedSeats.reduce((sum, seat) => sum + seat.price, 0);
    },

    get totalStandingPrice() {
        return this.standingCount * this.standingPrice;
    },

    get totalTierPrice() {
        let sum = 0;
        Object.keys(this.tierQuantities).forEach(k => {
            const count = this.tierQuantities[k] || 0;
            const price = this.tierMeta[k]?.price || this.basePrice;
            sum += count * price;
        });
        return sum;
    },

    get totalPrice() {
        return this.totalSeatPrice + this.totalStandingPrice + this.totalTierPrice;
    },

    get totalTierCount() {
        return Object.values(this.tierQuantities).reduce((a, b) => a + b, 0);
    },

    get totalTicketCount() {
        return this.selectedSeats.length + this.standingCount + this.totalTierCount;
    },

    get formattedTotalPrice() {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(this.totalPrice);
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
                this.notify(`Ghế ${lost.map(seat => seat.row + seat.number).join(', ')} vừa được người khác giữ.`, 'warning');
            }
        } catch (error) {
            // mất mạng tạm thời: bỏ qua, lần sau thử lại
        }
    },

    // Gửi ghế/hạng vé đang chọn lên server để giữ chỗ 10 phút
    async confirmHold() {
        if (this.totalTicketCount === 0 || this.isSubmitting) return;
        this.isSubmitting = true;

        const tiers = {};
        Object.entries(this.tierQuantities).forEach(([key, quantity]) => {
            if (quantity > 0) tiers[key] = quantity;
        });
        if (this.standingCount > 0) tiers.standing_pit = this.standingCount;

        try {
            const response = await fetch(config.holdUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ seat_ids: this.selectedSeats.map(seat => seat.id), tiers }),
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
        this.toastTimeout = setTimeout(() => {
            this.showToast = false;
        }, 3200);
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
        fullName: config.userName || 'Nguyễn Văn Kiên',
        phone: config.userPhone || '0988 123 456',
        email: config.userEmail || 'kien.nguyen@example.com',
        idCard: '001201008899',
        city: 'Hà Nội',
        address: 'Số 18 Hoàng Diệu, Ba Đình',
        notes: 'Gửi vé qua Email và SMS',
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
    preset: config.preset || 'mega_concert',
    roomName: config.roomName || 'Sân Vận Động Quân Khu 7 (SVĐ QK7 Arena)',
    roomAddress: config.roomAddress || 'Số 202 Hoàng Văn Thụ, Phường 9, Phú Nhuận, TP.HCM',
    capacity: config.capacity || 20000,
    basePrice: config.basePrice || 250000,
    rows: config.rows || 10,
    cols: config.cols || 14,
    vipRatio: config.vipRatio || 30,
    svipRatio: config.svipRatio || 15,
    activeZoneFilter: 'all',
    hoveredSeat: null,
    zoomScale: 1,

    presetsMeta: {
        mega_concert: {
            name: 'Mega Concert Stadium Arena',
            subtitle: 'Sân vận động siêu quy mô (20.000+ chỗ, Catwalk Runway, B-Stage, GA Standing)',
            capacityDefault: 20000,
            icon: '🏟️',
            badge: 'badge-gold',
        },
        theater_hall: {
            name: 'Nhà Hát & Giao Hưởng (Theater Hall)',
            subtitle: 'Khán phòng nghệ thuật (Tầng Trệt Stalls, Dress Circle, Gallery, VIP Boxes)',
            capacityDefault: 1200,
            icon: '🎭',
            badge: 'badge-rose',
        },
        convention_center: {
            name: 'Trung Tâm Hội Nghị & Expo',
            subtitle: 'Hội trường diễn đàn (Khu Keynote VIP, Standard, Bàn tròn VIP Business)',
            capacityDefault: 800,
            icon: '🏢',
            badge: 'badge-sage',
        },
        custom_grid: {
            name: 'Ma Trận Tùy Chỉnh (Custom Grid)',
            subtitle: 'Tự do cấu hình số hàng, số cột và tỷ lệ phân bổ hạng vé theo yêu cầu',
            capacityDefault: 300,
            icon: '⚡',
            badge: 'badge-dark',
        }
    },

    selectPreset(p) {
        this.preset = p;
        if (this.presetsMeta[p]) {
            this.capacity = this.presetsMeta[p].capacityDefault;
        }
    },

    zoomIn() {
        if (this.zoomScale < 1.3) this.zoomScale = parseFloat((this.zoomScale + 0.1).toFixed(1));
    },

    zoomOut() {
        if (this.zoomScale > 0.8) this.zoomScale = parseFloat((this.zoomScale - 0.1).toFixed(1));
    },

    resetZoom() {
        this.zoomScale = 1;
    },

    get generatedTiers() {
        const base = parseFloat(this.basePrice) || 250000;
        if (this.preset === 'mega_concert') {
            return [
                {
                    key: 'svip_diamond',
                    name: 'SVIP Diamond B-Stage Floor',
                    label: 'SVIP Diamond',
                    price: Math.round(base * 2.8),
                    color: '#D4AF37',
                    badge: 'badge-gold',
                    perks: ['Ghế cạnh sân khấu B-Stage', 'Vòng tay VIP', 'Soundcheck Pass', 'Lối đi riêng'],
                    multiplier: 'x2.8',
                    gate: 'Cổng VIP A1',
                },
                {
                    key: 'vip_gold',
                    name: 'VIP Gold Catwalk Floor',
                    label: 'VIP Gold',
                    price: Math.round(base * 1.8),
                    color: '#C08497',
                    badge: 'badge-rose',
                    perks: ['Sàn Catwalk trung tâm', 'Set quà tặng lưu niệm', 'Check-in line VIP'],
                    multiplier: 'x1.8',
                    gate: 'Cổng VIP A2',
                },
                {
                    key: 'cat1_stand',
                    name: 'Khán Đài A (Trung Tâm Tầng 1)',
                    label: 'Khán Đài A',
                    price: Math.round(base * 1.2),
                    color: '#3A5A40',
                    badge: 'badge-sage',
                    perks: ['Tầm nhìn trực diện', 'Ghế đệm tiêu chuẩn', 'Áo mưa / Quạt sự kiện'],
                    multiplier: 'x1.2',
                    gate: 'Cổng Đông 1',
                },
                {
                    key: 'cat2_wings',
                    name: 'Khán Đài Cánh B (Tầng 2)',
                    label: 'Khán Đài B',
                    price: Math.round(base * 1.0),
                    color: '#1F2937',
                    badge: 'badge-dark',
                    perks: ['Bao quát toàn cảnh sân vận động', 'Hòa âm đa tầng'],
                    multiplier: 'x1.0',
                    gate: 'Cổng Tây 2',
                },
                {
                    key: 'skybox_suite',
                    name: 'Skybox VIP Suites (Tầng Thượng)',
                    label: 'Skybox VIP',
                    price: Math.round(base * 3.5),
                    color: '#8D6E00',
                    badge: 'badge-gold',
                    perks: ['Phòng suite riêng biệt', 'Tiệc Finger Food & Rượu vang', 'Quản gia hỗ trợ'],
                    multiplier: 'x3.5',
                    gate: 'Thang máy VIP',
                },
            ];
        } else if (this.preset === 'theater_hall') {
            return [
                {
                    key: 'vip_stalls',
                    name: 'Tầng Trệt VIP Stalls',
                    label: 'VIP Stalls',
                    price: Math.round(base * 2.0),
                    color: '#D4AF37',
                    badge: 'badge-gold',
                    perks: ['Hàng ghế trung tâm sân khấu', 'Âm học chuẩn thính phòng', 'Brochure chương trình'],
                    multiplier: 'x2.0',
                    gate: 'Cửa Trệt A',
                },
                {
                    key: 'dress_circle',
                    name: 'Khán Đài Dress Circle (Tầng 1)',
                    label: 'Dress Circle',
                    price: Math.round(base * 1.4),
                    color: '#C08497',
                    badge: 'badge-rose',
                    perks: ['Góc nhìn bao quát toàn bộ dàn nhạc', 'Ghế nhung sang trọng'],
                    multiplier: 'x1.4',
                    gate: 'Cửa Tầng 1',
                },
                {
                    key: 'upper_gallery',
                    name: 'Ban Công Upper Gallery (Tầng 2)',
                    label: 'Upper Gallery',
                    price: Math.round(base * 1.0),
                    color: '#3A5A40',
                    badge: 'badge-sage',
                    perks: ['Thưởng thức trọn vẹn âm thanh vòm'],
                    multiplier: 'x1.0',
                    gate: 'Cửa Tầng 2',
                },
            ];
        } else {
            return [
                {
                    key: 'keynote_vip',
                    name: 'Hàng Ghế Keynote VIP',
                    label: 'Keynote VIP',
                    price: Math.round(base * 1.8),
                    color: '#D4AF37',
                    badge: 'badge-gold',
                    perks: ['Hàng ghế 1-3 trước sân khấu', 'Giao lưu cùng Diễn giả', 'Buffet trưa'],
                    multiplier: 'x1.8',
                    gate: 'Cổng VIP',
                },
                {
                    key: 'standard_seat',
                    name: 'Ghế Tiêu Chuẩn Hội Nghị',
                    label: 'Standard Seat',
                    price: Math.round(base * 1.0),
                    color: '#3A5A40',
                    badge: 'badge-sage',
                    perks: ['Tài liệu hội thảo điện tử', 'Teabreak giải lao'],
                    multiplier: 'x1.0',
                    gate: 'Cổng Chính',
                },
            ];
        }
    },

    get calculatedTotalSeats() {
        if (this.preset === 'mega_concert') {
            return 210;
        } else if (this.preset === 'theater_hall') {
            return 112;
        } else if (this.preset === 'convention_center') {
            return 108;
        } else {
            return this.rows * this.cols;
        }
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
