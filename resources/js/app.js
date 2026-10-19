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
    zoneQuantities: {},
    zoneMeta: {},
    basePrice: config.basePrice || 180000,
    activeZoneFilter: 'all',
    hoveredSeat: null,
    hoverTimeout: null,
    zoomScale: 1,
    standingCount: 0,
    standingPrice: config.standingPrice || 0,
    notificationMessage: '',
    notificationType: 'info',
    showToast: false,
    toastTimeout: null,
    isSubmitting: false,
    unavailableIds: [],

    concertViewMode: config.defaultViewMode || 'zone',
    selectedZone: 'svip_diamond',
    activeVenueZone: null,
    hoveredZone: null,
    availableSeatsByZone: config.availableSeatsByZone || {},
    seatInventory: config.seatInventory || {},

    init() {
        if (config.tiers) {
            Object.keys(config.tiers).forEach(k => {
                this.tierQuantities[k] = 0;
                this.tierMeta[k] = config.tiers[k];
            });
        }
        (config.zones || []).forEach(zone => {
            this.zoneQuantities[zone.id] = 0;
            this.zoneMeta[zone.id] = zone;
        });
        this.activeVenueZone = null;
        if (config.pollStatus && config.statusUrl) {
            setInterval(() => this.refreshSeatStatus(), 15000);
        }
    },

    switchViewMode(mode) {
        this.concertViewMode = mode;
    },

    selectConcertZone(zoneKey, autoIncrement = false) {
        this.selectedZone = zoneKey;
        this.activeZoneFilter = zoneKey;
        if (autoIncrement && this.getZoneSelectedCount(zoneKey) === 0) {
            this.quickPickSeatFromZone(zoneKey);
        }
        const el = document.getElementById('zone-card-' + zoneKey);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    },

    setHoveredZone(zoneKey) {
        this.hoveredZone = zoneKey;
    },

    clearHoveredZone() {
        this.hoveredZone = null;
    },

    focusVenueZone(zoneId) {
        this.activeVenueZone = zoneId;
    },

    focusZoneInSeatGrid(zoneKey) {
        this.concertViewMode = 'seat';
        this.activeZoneFilter = zoneKey;
        const zoneName = this.tierMeta[zoneKey]?.name || zoneKey;
        this.notify(`Đang xem vị trí từng ghế khu: ${zoneName}`, 'info');
    },

    getZoneSelectedCount(zoneKey) {
        if (this.zoneQuantities[zoneKey] !== undefined) return this.zoneQuantities[zoneKey] || 0;
        if (zoneKey === 'standing_pit') return this.standingCount;
        return this.selectedSeats.filter(s => s.type === zoneKey).length;
    },

    incrementZone(zoneId) {
        const zone = this.zoneMeta[zoneId];
        if (!zone || this.totalTicketCount >= 8 || this.zoneQuantities[zoneId] >= zone.available) return;
        this.zoneQuantities[zoneId]++;
    },

    decrementZone(zoneId) {
        if (this.zoneQuantities[zoneId] > 0) this.zoneQuantities[zoneId]--;
    },

    toggleTable(zoneId, rowLabel) {
        const rowSeats = (this.seatInventory[zoneId] || []).filter(seat => (seat.row ?? seat.row_label) === rowLabel && seat.status === 'available' && !this.unavailableIds.includes(seat.id));
        if (!rowSeats.length) return;
        const selected = rowSeats.filter(seat => this.isSeatSelected(seat.id));
        if (selected.length === rowSeats.length) {
            selected.forEach(seat => this.removeSeat(seat.id));
            return;
        }
        rowSeats.filter(seat => !this.isSeatSelected(seat.id)).forEach(seat => {
            if (this.totalTicketCount < 8) this.toggleSeat(seat.id, seat.number ?? seat.seat_number, seat.row ?? seat.row_label, seat.type, seat.typeName ?? seat.type_name, seat.status, seat.price, seat.perks);
        });
    },

    quickPickSeatFromZone(zoneKey) {
        if (zoneKey === 'standing_pit') {
            return this.addStandingTicket();
        }

        if (this.totalTicketCount >= 8) {
            this.notify('Bạn chỉ có thể chọn tối đa 8 vé cho mỗi lượt đặt.', 'warning');
            return;
        }

        const zoneSeats = this.availableSeatsByZone[zoneKey] || [];
        const availableSeat = zoneSeats.find(s => 
            s.status === 'available' && 
            !this.unavailableIds.includes(s.id) && 
            !this.isSeatSelected(s.id)
        );

        if (!availableSeat) {
            this.notify('Khu vực này hiện đã hết ghế trống khả dụng!', 'warning');
            return;
        }

        this.toggleSeat(
            availableSeat.id,
            availableSeat.number,
            availableSeat.row,
            availableSeat.type,
            availableSeat.typeName,
            availableSeat.status,
            availableSeat.price,
            availableSeat.perks
        );
    },

    quickRemoveSeatFromZone(zoneKey) {
        if (zoneKey === 'standing_pit') {
            return this.removeStandingTicket();
        }

        const pickedSeats = this.selectedSeats.filter(s => s.type === zoneKey);
        if (pickedSeats.length === 0) return;

        const lastSeat = pickedSeats[pickedSeats.length - 1];
        this.removeSeat(lastSeat.id);
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
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
            this.hoverTimeout = null;
        }
        this.hoveredSeat = seatData;
    },

    clearHoveredSeat() {
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
        }
        this.hoverTimeout = setTimeout(() => {
            this.hoveredSeat = null;
        }, 120);
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
        return this.totalSeatPrice + this.totalStandingPrice + this.totalTierPrice + this.totalZonePrice;
    },

    get totalZonePrice() {
        return Object.entries(this.zoneQuantities).reduce((sum, [id, quantity]) => sum + quantity * (this.zoneMeta[id]?.price || 0), 0);
    },

    get totalTierCount() {
        return Object.values(this.tierQuantities).reduce((a, b) => a + b, 0);
    },

    get totalTicketCount() {
        return this.selectedSeats.length + this.standingCount + this.totalTierCount + Object.values(this.zoneQuantities).reduce((sum, count) => sum + count, 0);
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
            const { unavailable, zones_available: zonesAvailable = {} } = await response.json();
            this.unavailableIds = unavailable;
            Object.entries(zonesAvailable).forEach(([id, available]) => {
                if (this.zoneMeta[id]) this.zoneMeta[id].available = available;
            });

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
        const zones = {};
        Object.entries(this.zoneQuantities).forEach(([id, quantity]) => { if (quantity > 0) zones[id] = quantity; });

        try {
            const response = await fetch(config.holdUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ seat_ids: this.selectedSeats.map(seat => seat.id), tiers, zones }),
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
            const removeUrl = item.isZoneTicket ? config.removeZoneUrl : config.removeUrl;
            const response = await fetch(removeUrl.replace('__ID__', item.id), {
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
        const seats = this.items.map(i => i.is_zone_ticket ? i.seat_type : (i.row_label ? i.row_label + i.seat_number : i.seat_type)).join(', ') || 'Ghế C4, C5';

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
    selectedTool: 'svip_diamond',
    matrixRows: (config.initialRows && config.initialRows.length) ? JSON.parse(JSON.stringify(config.initialRows)) : [],

    stageType: config.stageType || (config.preset === 'sayhi_concert_d2' ? 'runway_y' : ((config.preset === 'sayhi_concert' || config.preset === 'mega_concert') ? 'runway_t' : 'standard')),
    standingZones: (config.standingZones && config.standingZones.length) ? JSON.parse(JSON.stringify(config.standingZones)) : [
        { code: 'FZ-A', name: 'Super Fan Fanzone A', capacity: 30 },
        { code: 'FZ-B', name: 'Super Fan Fanzone B', capacity: 30 },
    ],

    tools: [
        { id: 'svip_diamond', name: 'SVIP Diamond', color: '#D4AF37', icon: '💎', border: 'border-amber-400' },
        { id: 'vip_gold', name: 'VIP Gold', color: '#C08497', icon: '⭐', border: 'border-pink-300' },
        { id: 'cat1_stand', name: 'Khán Đài Cat 1', color: '#3A5A40', icon: '✨', border: 'border-emerald-400' },
        { id: 'cat2_wings', name: 'Cánh Cat 2', color: '#1E293B', icon: '🟦', border: 'border-blue-400' },
        { id: 'skybox_suite', name: 'Skybox VIP', color: '#E11D48', icon: '🥂', border: 'border-rose-400' },
        { id: 'aisle', name: 'Lối Đi (Aisle)', color: '#374151', icon: '🚫', border: 'border-dashed border-gray-400' },
        { id: 'blocked', name: 'Ghế Khóa/Kỹ Thuật', color: '#4B5563', icon: '🛠️', border: 'border-red-400' },
    ],

    init() {
        if (!this.matrixRows || this.matrixRows.length === 0) {
            this.buildDefaultMatrix(this.preset);
        }
    },

    setStageType(type) {
        this.stageType = type;
        if (type === 'runway_y') {
            this.generateYStageCutout();
        }
    },

    addStandingZone() {
        const nextLetter = String.fromCharCode(65 + this.standingZones.length);
        this.standingZones.push({
            code: 'FZ-' + nextLetter,
            name: 'Fanzone Khu ' + nextLetter,
            capacity: 25
        });
        this.syncCapacity();
    },

    removeStandingZone(idx) {
        if (this.standingZones.length <= 1) return;
        this.standingZones.splice(idx, 1);
        this.syncCapacity();
    },

    get totalStandingCapacity() {
        if (!['mega_concert', 'sayhi_concert', 'sayhi_concert_d2'].includes(this.preset)) {
            return 0;
        }
        return this.standingZones.reduce((sum, z) => sum + (parseInt(z.capacity) || 0), 0);
    },

    generateYStageCutout() {
        if (this.matrixRows.length >= 4) {
            const rowsToCut = Math.min(4, this.matrixRows.length);
            for (let r = 0; r < rowsToCut; r++) {
                const totalSeats = this.matrixRows[r].seats.length;
                const mid = Math.floor(totalSeats / 2);
                if (r === 0) {
                    for (let s = Math.max(0, mid - 2); s < Math.min(totalSeats, mid + 2); s++) {
                        this.matrixRows[r].seats[s].is_aisle = true;
                    }
                } else if (r === 1) {
                    if (mid > 0) this.matrixRows[r].seats[mid - 1].is_aisle = true;
                    if (mid < totalSeats) this.matrixRows[r].seats[mid].is_aisle = true;
                } else if (r === 2) {
                    for (let s = Math.max(0, mid - 2); s < Math.min(totalSeats, mid + 2); s++) {
                        this.matrixRows[r].seats[s].is_aisle = true;
                    }
                } else if (r === 3) {
                    for (let s = Math.max(0, mid - 3); s < Math.min(totalSeats, mid + 3); s++) {
                        this.matrixRows[r].seats[s].is_aisle = true;
                    }
                }
            }
        }
        this.syncCapacity();
    },

    generateCenterAisle() {
        this.matrixRows.forEach(r => {
            const total = r.seats.length;
            const mid = Math.floor(total / 2);
            if (mid > 0) r.seats[mid - 1].is_aisle = true;
            if (mid < total) r.seats[mid].is_aisle = true;
        });
        this.syncCapacity();
    },

    generateSideAisles() {
        this.matrixRows.forEach(r => {
            const total = r.seats.length;
            const p1 = Math.floor(total / 3);
            const p2 = Math.floor((total * 2) / 3);
            if (p1 < total) r.seats[p1].is_aisle = true;
            if (p2 < total) r.seats[p2].is_aisle = true;
        });
        this.syncCapacity();
    },

    restoreAllSeats() {
        this.matrixRows.forEach(r => {
            r.seats.forEach(s => {
                s.is_aisle = false;
                s.is_blocked = false;
            });
        });
        this.syncCapacity();
    },

    promptRenameRow(rIdx) {
        const current = this.matrixRows[rIdx]?.row || '';
        const newName = prompt('Nhập tên/nhãn mới cho hàng ghế này:', current);
        if (newName && newName.trim() !== '') {
            this.matrixRows[rIdx].row = newName.trim();
        }
    },

    buildDefaultMatrix(preset) {
        if (preset === 'mega_concert') {
            this.stageType = 'runway_t';
            const defs = [
                ['SVIP-A', 12, 'svip_diamond'], ['SVIP-B', 12, 'svip_diamond'],
                ['FL-1', 14, 'vip_gold'], ['FL-2', 14, 'vip_gold'], ['FL-3', 14, 'vip_gold'], ['FL-4', 14, 'vip_gold'],
                ['A1', 16, 'cat1_stand'], ['A2', 16, 'cat1_stand'], ['A3', 16, 'cat1_stand'],
                ['B1', 14, 'cat1_stand'], ['B2', 14, 'cat1_stand'],
                ['C1', 12, 'cat2_wings'], ['C2', 12, 'cat2_wings'],
                ['D1', 12, 'cat2_wings'], ['D2', 12, 'cat2_wings'],
                ['SB', 8, 'skybox_suite'],
            ];
            this.matrixRows = defs.map(([label, count, type]) => ({
                row: label,
                seats: Array.from({ length: count }, (_, i) => ({
                    number: i + 1,
                    type: type,
                    is_aisle: false,
                    is_blocked: false,
                }))
            }));
        } else if (preset === 'sayhi_concert') {
            this.stageType = 'runway_t';
            const defs = [
                ['SVIP-SH1', 12, 'svip_diamond'], ['SVIP-SH2', 12, 'svip_diamond'],
                ['VIP-SH1', 14, 'vip_gold'], ['VIP-SH2', 14, 'vip_gold'], ['VIP-SH3', 14, 'vip_gold'], ['VIP-SH4', 14, 'vip_gold'],
                ['CAT1-A1', 16, 'cat1_stand'], ['CAT1-A2', 16, 'cat1_stand'], ['CAT1-A3', 16, 'cat1_stand'],
                ['CAT2-B1', 14, 'cat1_stand'], ['CAT2-B2', 14, 'cat1_stand'],
                ['CAT3-C1', 12, 'cat2_wings'], ['CAT3-C2', 12, 'cat2_wings'],
                ['CAT3-D1', 12, 'cat2_wings'], ['CAT3-D2', 12, 'cat2_wings'],
                ['SKYLNG', 8, 'skybox_suite'],
            ];
            this.matrixRows = defs.map(([label, count, type]) => ({
                row: label,
                seats: Array.from({ length: count }, (_, i) => ({
                    number: i + 1,
                    type: type,
                    is_aisle: false,
                    is_blocked: false,
                }))
            }));
        } else if (preset === 'sayhi_concert_d2') {
            this.stageType = 'runway_y';
            this.standingZones = [
                { code: 'FZ-A', name: 'Super Fan Fanzone A', capacity: 30 },
                { code: 'FZ-B', name: 'Super Fan Fanzone B', capacity: 30 },
            ];
            const defs = [
                ['SVIP-D2-1', 14, 'svip_diamond'], ['SVIP-D2-2', 14, 'svip_diamond'],
                ['VIP-D2-1', 16, 'vip_gold'], ['VIP-D2-2', 16, 'vip_gold'], ['VIP-D2-3', 16, 'vip_gold'], ['VIP-D2-4', 16, 'vip_gold'],
                ['CAT1-D2-1', 18, 'cat1_stand'], ['CAT1-D2-2', 18, 'cat1_stand'], ['CAT1-D2-3', 18, 'cat1_stand'],
                ['CAT2-D2-1', 16, 'cat1_stand'], ['CAT2-D2-2', 16, 'cat1_stand'],
                ['CAT3-C1', 14, 'cat2_wings'], ['CAT3-C2', 14, 'cat2_wings'],
                ['CAT3-D1', 14, 'cat2_wings'], ['CAT3-D2', 14, 'cat2_wings'],
                ['SKYLNG-1', 8, 'skybox_suite'], ['SKYLNG-2', 8, 'skybox_suite'],
            ];
            this.matrixRows = defs.map(([label, count, type]) => ({
                row: label,
                seats: Array.from({ length: count }, (_, i) => ({
                    number: i + 1,
                    type: type,
                    is_aisle: false,
                    is_blocked: false,
                }))
            }));
            this.generateYStageCutout();
        } else if (preset === 'theater_hall') {
            this.stageType = 'standard';
            const defs = [
                ['ST-1', 16, 'vip_gold'], ['ST-2', 16, 'vip_gold'],
                ['ST-3', 16, 'cat1_stand'], ['DC-1', 16, 'cat1_stand'], ['DC-2', 16, 'cat1_stand'],
                ['GL-1', 16, 'cat2_wings'], ['GL-2', 16, 'cat2_wings'],
            ];
            this.matrixRows = defs.map(([label, count, type]) => ({
                row: label,
                seats: Array.from({ length: count }, (_, i) => ({
                    number: i + 1,
                    type: type,
                    is_aisle: false,
                    is_blocked: false,
                }))
            }));
        } else if (preset === 'convention_center') {
            this.stageType = 'standard';
            const defs = [
                ['KN-A', 18, 'svip_diamond'], ['KN-B', 18, 'svip_diamond'],
                ['STD-1', 18, 'cat1_stand'], ['STD-2', 18, 'cat1_stand'],
                ['STD-3', 18, 'cat1_stand'], ['STD-4', 18, 'cat1_stand'],
            ];
            this.matrixRows = defs.map(([label, count, type]) => ({
                row: label,
                seats: Array.from({ length: count }, (_, i) => ({
                    number: i + 1,
                    type: type,
                    is_aisle: false,
                    is_blocked: false,
                }))
            }));
        } else {
            this.stageType = 'standard';
            const rCount = Math.max(2, Math.min(26, parseInt(this.rows) || 10));
            const cCount = Math.max(4, Math.min(40, parseInt(this.cols) || 14));
            this.matrixRows = [];
            for (let r = 0; r < rCount; r++) {
                const label = String.fromCharCode(65 + r);
                const type = r < 2 ? 'svip_diamond' : (r < 5 ? 'vip_gold' : 'cat1_stand');
                this.matrixRows.push({
                    row: label,
                    seats: Array.from({ length: cCount }, (_, i) => ({
                        number: i + 1,
                        type: type,
                        is_aisle: false,
                        is_blocked: false,
                    }))
                });
            }
        }
    },

    selectPreset(p) {
        this.preset = p;
        this.buildDefaultMatrix(p);
        this.syncCapacity();
    },

    syncCapacity() {
        this.capacity = this.activeSeatCount + this.totalStandingCapacity;
    },

    paintSeat(rowIdx, seatIdx) {
        const seat = this.matrixRows[rowIdx]?.seats[seatIdx];
        if (!seat) return;

        if (this.selectedTool === 'aisle') {
            seat.is_aisle = !seat.is_aisle;
        } else if (this.selectedTool === 'blocked') {
            seat.is_blocked = !seat.is_blocked;
            if (seat.is_blocked) seat.is_aisle = false;
        } else {
            seat.is_aisle = false;
            seat.is_blocked = false;
            seat.type = this.selectedTool;
        }
        this.syncCapacity();
    },

    paintRow(rowIdx) {
        const row = this.matrixRows[rowIdx];
        if (!row) return;

        row.seats.forEach(seat => {
            if (this.selectedTool === 'aisle') {
                seat.is_aisle = true;
            } else if (this.selectedTool === 'blocked') {
                seat.is_blocked = true;
                seat.is_aisle = false;
            } else {
                seat.is_aisle = false;
                seat.is_blocked = false;
                seat.type = this.selectedTool;
            }
        });
        this.syncCapacity();
    },

    addRow() {
        const nextIdx = this.matrixRows.length;
        const label = nextIdx < 26 ? String.fromCharCode(65 + nextIdx) : 'R' + (nextIdx + 1);
        const colCount = this.matrixRows.length > 0 ? this.matrixRows[0].seats.length : 14;
        this.matrixRows.push({
            row: label,
            seats: Array.from({ length: colCount }, (_, i) => ({
                number: i + 1,
                type: this.selectedTool === 'aisle' || this.selectedTool === 'blocked' ? 'cat1_stand' : this.selectedTool,
                is_aisle: false,
                is_blocked: false,
            }))
        });
        this.syncCapacity();
    },

    removeRow(rowIdx) {
        if (this.matrixRows.length <= 1) return;
        this.matrixRows.splice(rowIdx, 1);
        this.syncCapacity();
    },

    addSeatToRow(rowIdx) {
        const row = this.matrixRows[rowIdx];
        if (!row) return;
        const newNumber = row.seats.length + 1;
        row.seats.push({
            number: newNumber,
            type: this.selectedTool === 'aisle' || this.selectedTool === 'blocked' ? 'cat1_stand' : this.selectedTool,
            is_aisle: false,
            is_blocked: false,
        });
        this.syncCapacity();
    },

    removeSeatFromRow(rowIdx) {
        const row = this.matrixRows[rowIdx];
        if (!row || row.seats.length <= 1) return;
        row.seats.pop();
        this.syncCapacity();
    },

    getSeatColor(seat) {
        if (seat.is_aisle) return 'transparent';
        if (seat.is_blocked) return '#4B5563';
        const colors = {
            svip_diamond: '#D4AF37',
            vip_gold: '#C08497',
            cat1_stand: '#3A5A40',
            cat2_wings: '#1E293B',
            skybox_suite: '#E11D48',
        };
        return colors[seat.type] || '#4B5563';
    },

    get activeSeatCount() {
        let total = 0;
        this.matrixRows.forEach(r => {
            r.seats.forEach(s => {
                if (!s.is_aisle) total++;
            });
        });
        return total;
    },

    get matrixStats() {
        const stats = { svip: 0, vip: 0, cat1: 0, cat2: 0, skybox: 0, aisles: 0, blocked: 0 };
        this.matrixRows.forEach(r => {
            r.seats.forEach(s => {
                if (s.is_aisle) stats.aisles++;
                else if (s.is_blocked) stats.blocked++;
                else if (s.type === 'svip_diamond') stats.svip++;
                else if (s.type === 'vip_gold') stats.vip++;
                else if (s.type === 'cat1_stand') stats.cat1++;
                else if (s.type === 'cat2_wings') stats.cat2++;
                else if (s.type === 'skybox_suite') stats.skybox++;
            });
        });
        return stats;
    },

    presetsMeta: {
        sayhi_concert_d2: {
            name: 'Live Concert Anh Trai "Say Hi" - Đêm 2',
            subtitle: 'Sân khấu Runway chữ Y (Y-Stage Catwalk) & Fanzone mở rộng (310 chỗ)',
            capacityDefault: 310,
            icon: '🌟',
            badge: 'badge-gold',
        },
        mega_concert: {
            name: 'Mega Concert Stadium Arena',
            subtitle: 'Sân vận động siêu quy mô (252 chỗ: 212 ghế khán đài + 40 vé đứng GA Fanzone)',
            capacityDefault: 252,
            icon: '🏟️',
            badge: 'badge-gold',
        },
        sayhi_concert: {
            name: 'Live Concert Anh Trai "Say Hi"',
            subtitle: 'Sân khấu Runway T-Stage đỉnh cao (252 chỗ: 212 ghế khán đài + 40 vé đứng Super Fan Fanzone Say Hi A/B)',
            capacityDefault: 252,
            icon: '🎤',
            badge: 'badge-rose',
        },
        theater_hall: {
            name: 'Nhà Hát & Giao Hưởng (Theater Hall)',
            subtitle: 'Khán phòng nghệ thuật (112 chỗ: Tầng Trệt Stalls, Dress Circle, Gallery)',
            capacityDefault: 112,
            icon: '🎭',
            badge: 'badge-rose',
        },
        convention_center: {
            name: 'Trung Tâm Hội Nghị & Expo',
            subtitle: 'Hội trường diễn đàn (108 chỗ: Khu Keynote VIP, Standard Expo)',
            capacityDefault: 108,
            icon: '🏢',
            badge: 'badge-sage',
        },
        custom_grid: {
            name: 'Ma Trận Tùy Chỉnh (Custom Grid)',
            subtitle: 'Tự do cấu hình số hàng, số cột và tỷ lệ phân bổ hạng vé theo yêu cầu',
            capacityDefault: 140,
            icon: '⚡',
            badge: 'badge-dark',
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
        if (this.preset === 'sayhi_concert_d2') {
            return [
                {
                    key: 'svip_diamond',
                    name: 'SVIP Diamond Đêm 2 (Y-Stage Runway)',
                    label: 'SVIP Diamond D2',
                    price: Math.round(base * 3.0),
                    color: '#D4AF37',
                    badge: 'badge-gold',
                    perks: ['Ôm trọn ngã 3 sân khấu chữ Y', 'Soundcheck Pass độc quyền', 'Bộ Giftset Đêm 2 đặc biệt', 'Lối đi thảm đỏ riêng'],
                    multiplier: 'x3.0',
                    gate: 'Cổng VIP Kim Cương',
                },
                {
                    key: 'vip_gold',
                    name: 'VIP Gold Đêm 2 (Wings & Catwalk)',
                    label: 'VIP Gold D2',
                    price: Math.round(base * 2.0),
                    color: '#C08497',
                    badge: 'badge-rose',
                    perks: ['Sàn Catwalk trung tâm cánh chữ Y', 'Set quà lưu niệm Đêm 2', 'Check-in line riêng'],
                    multiplier: 'x2.0',
                    gate: 'Cổng VIP Gold',
                },
                {
                    key: 'cat1_stand',
                    name: 'Khán Đài Khán Giả CAT 1 & 2',
                    label: 'Khán Đài CAT 1-2',
                    price: Math.round(base * 1.3),
                    color: '#3A5A40',
                    badge: 'badge-sage',
                    perks: ['Góc nhìn trực diện sân khấu', 'Ghế đệm tiêu chuẩn', 'Bộ quạt cổ vũ Đêm 2'],
                    multiplier: 'x1.3',
                    gate: 'Cổng Khán Đài Đông/Tây',
                },
                {
                    key: 'cat2_wings',
                    name: 'Khán Đài CAT 3 (Tầng Cao & Cánh)',
                    label: 'Khán Đài CAT 3',
                    price: Math.round(base * 1.0),
                    color: '#1F2937',
                    badge: 'badge-dark',
                    perks: ['Bao quát toàn cảnh sân khấu và biển lightstick', 'Hòa âm đa tầng'],
                    multiplier: 'x1.0',
                    gate: 'Cổng Cánh C/D',
                },
                {
                    key: 'skybox_suite',
                    name: 'Sky Lounge Diamond Đêm 2',
                    label: 'Sky Lounge D2',
                    price: Math.round(base * 4.0),
                    color: '#8D6E00',
                    badge: 'badge-gold',
                    perks: ['Phòng VIP riêng tư tầng thượng', 'Tiệc Finger Food & Champagne', 'Quản gia phục vụ'],
                    multiplier: 'x4.0',
                    gate: 'Thang máy Sky Lounge',
                },
            ];
        } else if (this.preset === 'sayhi_concert') {
            return [
                {
                    key: 'svip_diamond',
                    name: 'SVIP Say Hi Diamond B-Stage',
                    label: 'SVIP Diamond',
                    price: Math.round(base * 2.8),
                    color: '#D4AF37',
                    badge: 'badge-gold',
                    perks: ['Ghế cạnh Runway & B-Stage', 'Soundcheck Pass', 'Lightstick Say Hi', 'Lối đi riêng'],
                    multiplier: 'x2.8',
                    gate: 'Cổng VIP Say Hi',
                },
                {
                    key: 'vip_gold',
                    name: 'VIP Gold Say Hi Floor',
                    label: 'VIP Gold',
                    price: Math.round(base * 1.8),
                    color: '#C08497',
                    badge: 'badge-rose',
                    perks: ['Sàn Catwalk trung tâm', 'Set quà độc quyền Anh Trai Say Hi', 'Check-in VIP'],
                    multiplier: 'x1.8',
                    gate: 'Cổng VIP A2',
                },
                {
                    key: 'cat1_stand',
                    name: 'Khán Đài Say Hi CAT 1 (Tầng 1)',
                    label: 'Khán Đài CAT 1',
                    price: Math.round(base * 1.2),
                    color: '#3A5A40',
                    badge: 'badge-sage',
                    perks: ['Tầm nhìn trực diện', 'Ghế đệm êm có tựa', 'Quạt & Banner sự kiện'],
                    multiplier: 'x1.2',
                    gate: 'Cổng Khán Đài A',
                },
                {
                    key: 'cat2_wings',
                    name: 'Khán Đài Say Hi CAT 2 & 3 (Cánh)',
                    label: 'Khán Đài Cánh',
                    price: Math.round(base * 1.0),
                    color: '#1F2937',
                    badge: 'badge-dark',
                    perks: ['Bao quát toàn cảnh sân vận động', 'Hòa âm đa tầng'],
                    multiplier: 'x1.0',
                    gate: 'Cổng Khán Đài C/D',
                },
                {
                    key: 'skybox_suite',
                    name: 'Sky Lounge VIP Suites Say Hi',
                    label: 'Sky Lounge VIP',
                    price: Math.round(base * 3.5),
                    color: '#8D6E00',
                    badge: 'badge-gold',
                    perks: ['Phòng suite riêng tầng thượng', 'Tiệc Finger Food & Rượu vang', 'Quản gia hỗ trợ'],
                    multiplier: 'x3.5',
                    gate: 'Thang máy VIP',
                },
            ];
        } else if (this.preset === 'mega_concert') {
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
        if (this.preset === 'sayhi_concert_d2') {
            return 250;
        } else if (this.preset === 'mega_concert' || this.preset === 'sayhi_concert') {
            return 212;
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
