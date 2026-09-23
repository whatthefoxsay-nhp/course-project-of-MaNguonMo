<?php

namespace App\Support;

/**
 * Demo Data source for Event & Ticket Booking System.
 * Categories: Concert, Hòa Nhạc, Hội Thảo, Triển Lãm, Fan Meeting, Nhạc Kịch & Sân Khấu, Festival & Lễ Hội, Workshop & Masterclass.
 */
class DemoCatalog
{
    public static function categories(): array
    {
        return [
            (object) ['id' => 1, 'name' => 'Concert', 'slug' => 'concert', 'description' => 'Đại nhạc hội bùng nổ, liveshow ca sĩ và đêm nhạc sống quy mô lớn.'],
            (object) ['id' => 2, 'name' => 'Hòa Nhạc', 'slug' => 'hoa-nhac', 'description' => 'Đêm diễn giao hưởng thính phòng, opera và hòa tấu nghệ thuật cổ điển.'],
            (object) ['id' => 3, 'name' => 'Hội Thảo', 'slug' => 'hoi-thao', 'description' => 'Hội nghị công nghệ, diễn đàn kinh tế, tọa đàm chuyên gia và sự kiện doanh nghiệp.'],
            (object) ['id' => 4, 'name' => 'Triển Lãm', 'slug' => 'trien-lam', 'description' => 'Không gian nghệ thuật thị giác, triển lãm tương tác đa giác quan và hội chợ sáng tạo.'],
            (object) ['id' => 5, 'name' => 'Fan Meeting', 'slug' => 'fan-meeting', 'description' => 'Buổi gặp gỡ, giao lưu fansign, chụp ảnh và tương tác độc quyền cùng nghệ sĩ & thần tượng.'],
            (object) ['id' => 6, 'name' => 'Nhạc Kịch & Sân Khấu', 'slug' => 'san-khau-kich', 'description' => 'Vở kịch kinh điển, hài kịch sân khấu và nhạc kịch Broadway dàn dựng công phu.'],
            (object) ['id' => 7, 'name' => 'Festival & Lễ Hội', 'slug' => 'festival', 'description' => 'Lễ hội âm nhạc ngoài trời, lễ hội văn hóa - ẩm thực và sự kiện đếm ngược.'],
            (object) ['id' => 8, 'name' => 'Workshop & Masterclass', 'slug' => 'workshop', 'description' => 'Khóa đào tạo kỹ năng ngắn hạn, lớp học nghệ thuật và chia sẻ kinh nghiệm cùng chuyên gia.'],
        ];
    }

    public static function movies(): array
    {
        $categories = collect(self::categories())->keyBy('id');

        $rows = [
            [
                1, 'event', 'Live Concert: Anh Trai Vượt Ngàn Chông Gai 2026', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026', 1,
                'Đại nhạc hội bùng nổ quy tụ hơn 30 anh tài hàng đầu với sân khấu 360 độ, hiệu ứng pháo hoa, laser tiêu chuẩn quốc tế và âm thanh công nghệ L-Acoustics K1 đỉnh cao.',
                240, 250000, true,
                [
                    'venue_name' => 'Sân Vận Động Quân Khu 7 (SVĐ QK7 Arena)',
                    'venue_address' => 'Số 202 Hoàng Văn Thụ, Phường 9, Quận Phú Nhuận, TP. Hồ Chí Minh',
                    'venue_gates' => 'Cổng VIP (SVIP & Skybox), Cổng A (Khán đài A), Cổng C/D (Khán đài Cánh), Cổng GA (Fanzone)',
                    'parking_info' => 'Bãi giữ xe máy Cổng 2 (đường Phạm Văn Đồng), Bãi ô tô tầng hầm Nhà thi đấu QK7 (sức chứa 1.500 xe).',
                    'participants_title' => 'Dàn 33 Anh Tài & Khách Mời Đặc Biệt (Official Cast & Guests)',
                    'participants_summary' => 'Quy tụ 33 Anh Tài thế hệ vàng cùng Dàn Khách Mời Divas & Rapper đỉnh cao',
                    'host_mc' => 'MC Anh Tuấn & MC Khánh Vy',
                    'special_guests' => 'Hồ Ngọc Hà (Nữ Hoàng Khách Mời), Diva Mỹ Linh & Rapper Binz',
                    'timeline' => [
                        ['time' => '15:00 - 17:00', 'title' => 'Check-in & Đổi Vòng Tay (Wristband Exchange)', 'desc' => 'Đổi mã QR vé điện tử lấy vòng tay vào cổng & nhận set quà Merchandise VIP độc quyền.'],
                        ['time' => '16:30 - 17:15', 'title' => 'Đặc Quyền Soundcheck Rehearsal', 'desc' => 'Dành riêng cho hạng vé SVIP Kim Cương xem trực tiếp buổi tổng duyệt của các Anh Tài.'],
                        ['time' => '17:30', 'title' => 'Mở Toàn Bộ Cổng Soát Vé (Gate Opening)', 'desc' => 'Khán giả làm thủ tục qua cổng an ninh và ổn định chỗ ngồi tại các khán đài.'],
                        ['time' => '19:30 - 23:00', 'title' => 'Live Concert Chính Thức (Official Showtime)', 'desc' => 'Hơn 30 tiết mục bùng nổ, visual 3D mapping & pháo hoa nghệ thuật đỉnh cao.'],
                        ['time' => '23:15', 'title' => 'Bế Mạc & Điều Phối Giao Thông', 'desc' => 'Ban tổ chức hướng dẫn khán giả di chuyển theo các cửa thoát hiểm và xe buýt trung chuyển an toàn.']
                    ],
                    'lineup' => [
                        ['name' => 'Soobin Hoàng Sơn', 'role' => 'Ca Sĩ / Anh Tài', 'tag' => 'Team Bật Tình Yêu', 'badge' => 'King of Stage'],
                        ['name' => 'Bằng Kiều', 'role' => 'Ca Sĩ / Giọng Ca Vàng', 'tag' => 'Team Tinh Tinh', 'badge' => 'Legendary Vocal'],
                        ['name' => 'NSND Tự Long', 'role' => 'Nghệ Sĩ Nhân Dân', 'tag' => 'Team Danh Ca', 'badge' => 'National Artist'],
                        ['name' => 'Tuấn Hưng', 'role' => 'Ca Sĩ', 'tag' => 'Team Đam Mê', 'badge' => 'Rock & Ballad'],
                        ['name' => 'Cường Seven', 'role' => 'Ca Sĩ / Quán Quân', 'tag' => 'Team Thủ Lĩnh', 'badge' => 'Champion Captain'],
                        ['name' => 'Jun Phạm', 'role' => 'Ca Sĩ / Đạo Diễn', 'tag' => 'Team Sáng Tạo', 'badge' => 'Creative Director'],
                        ['name' => 'Rhymastic', 'role' => 'Music Producer / Rapper', 'tag' => 'Team Âm Nhạc', 'badge' => 'Hit Maker'],
                        ['name' => 'Kay Trần', 'role' => 'Ca Sĩ / Rapper', 'tag' => 'Team Trẻ Trung', 'badge' => 'Energy Booster'],
                        ['name' => 'Tiến Luật', 'role' => 'Diễn Viên / Nghệ Sĩ', 'tag' => 'Team Hài Hước', 'badge' => 'Fan Favorite'],
                        ['name' => 'BB Trần', 'role' => 'Diễn Viên / Sân Khấu', 'tag' => 'Team Biến Hóa', 'badge' => 'Performance'],
                        ['name' => 'Quốc Thiên', 'role' => 'Ca Sĩ Nội Lực', 'tag' => 'Team Giọng Ca', 'badge' => 'Master Vocalist'],
                        ['name' => 'Đinh Tiến Đạt', 'role' => 'Rapper Tiên Phong', 'tag' => 'Team Hip-hop', 'badge' => 'OG Rapper']
                    ],
                    'organizers' => [
                        'lead' => 'TicketBox Vietnam & 1Production Entertainment JSC',
                        'sponsor' => 'Techcombank (Nhà tài trợ Kim Cương) & Tiger Beer',
                        'media' => 'VTV3, VieON & YAN Digital Music'
                    ],
                    'entry_policy' => [
                        'age' => 'Sự kiện dành cho khán giả từ 12 tuổi trở lên. Khán giả dưới 14 tuổi phải có người lớn đi kèm.',
                        'checkin' => 'Vui lòng chuẩn bị sẵn mã QR vé điện tử (E-Ticket) trên ứng dụng và CCCD/VNeID để đối chiếu khi cần.',
                        'allowed' => 'Được mang điện thoại, sạc dự phòng, lightstick chính hãng, ví tiền, túi xách kích thước dưới 30x30cm.',
                        'prohibited' => 'CẤM mang máy ảnh cơ ống kính tele, gậy selfie, chai thủy tinh, bật lửa, pháo sáng, đồ ăn ngoài và chất kích thích.',
                        'refund' => 'Vé đã mua không được hoàn trả trừ trường hợp sự kiện bị hủy từ phía Ban tổ chức theo quy định của pháp luật.'
                    ]
                ]
            ],
            [
                2, 'event', 'Đêm Hòa Nhạc Giao Hưởng: The Sound of Saigon Philharmonic', 'hoa-nhac-giao-huong-saigon-philharmonic', 2,
                'Hành trình âm nhạc thính phòng đỉnh cao đưa khán giả đắm chìm trong các kiệt tác của Mozart, Beethoven và Tchaikovsky với dàn nhạc 80 nhạc công quốc tế.',
                120, 180000, true,
                [
                    'venue_name' => 'Khán Phòng Hòa Nhạc Saigon Grand Opera House',
                    'venue_address' => 'Nhà Hát Thành Phố, 07 Công Trường Lam Sơn, Bến Nghé, Quận 1, TP.HCM',
                    'venue_gates' => 'Cổng Chính Trục Lam Sơn (Khán đài Tầng 1 & Tầng 2), Cổng Ban Công Hoàng Gia (Box VIP)',
                    'parking_info' => 'Bãi giữ xe Nhà Hát TP và Bãi đỗ xe Khách sạn Caravelle (cách 50m).',
                    'participants_title' => 'Nhạc Trưởng Chỉ Huy, Nghệ Sĩ Độc Tấu & Dàn Nhạc 80 Nhạc Công',
                    'participants_summary' => 'Chỉ huy bởi Maestro Lê Phi Phi cùng Nghệ sĩ Violin Virtuoso Bùi Công Duy & Dàn Hợp Xướng',
                    'host_mc' => 'Nhà báo Âm nhạc Vũ Mạnh Cường',
                    'special_guests' => 'Nghệ Sĩ Piano Khách Mời Quốc Tế từ Budapest Symphony Orchestra',
                    'timeline' => [
                        ['time' => '18:30 - 19:15', 'title' => 'Đón Khách & Welcome Cocktail', 'desc' => 'Thưởng thức rượu vang và cocktail khai vị tại sảnh khánh tiết.'],
                        ['time' => '19:30', 'title' => 'Khai Mạc Phần 1: Tác Phẩm Cổ Điển Mozart & Beethoven', 'desc' => 'Đóng cửa khán phòng đúng 19:30 để đảm bảo không gian thưởng thức âm nhạc.'],
                        ['time' => '20:45 - 21:00', 'title' => 'Giải Lao Giữa Giờ (Intermission)', 'desc' => 'Giao lưu tại sảnh gương và chụp ảnh lưu niệm.'],
                        ['time' => '21:00 - 22:00', 'title' => 'Phần 2: Hòa Tấu Tchaikovsky & Giao Lưu Nhạc Trưởng', 'desc' => 'Phần biểu diễn cao trào và lời cảm ơn từ nhạc trưởng quốc tế.']
                    ],
                    'lineup' => [
                        ['name' => 'Maestro Lê Phi Phi', 'role' => 'Nhạc Trưởng Quốc Tế', 'tag' => 'Conductor Chỉ Huy', 'badge' => 'International Conductor'],
                        ['name' => 'Bùi Công Duy', 'role' => 'Nghệ Sĩ Violin Độc Tấu', 'tag' => 'Violin Solist', 'badge' => 'Violin Virtuoso'],
                        ['name' => 'Đào Tố Loan', 'role' => 'Diva Opera Soprano', 'tag' => 'Giọng Ca Thính Phòng', 'badge' => 'Opera Virtuoso'],
                        ['name' => 'Saigon Philharmonic Orchestra', 'role' => 'Dàn Nhạc Giao Hưởng', 'tag' => '80 Nhạc Công Quốc Tế', 'badge' => 'Symphony Ensemble']
                    ],
                    'organizers' => [
                        'lead' => 'Hội Âm Nhạc TP.HCM & Dàn Nhạc Giao Hưởng Sài Gòn',
                        'sponsor' => 'Vietnam Airlines & Quỹ Văn Hóa Nghệ Thuật',
                        'media' => 'HTV9 & Tạp Chí Âm Nhạc Cổ Điển'
                    ],
                    'entry_policy' => [
                        'age' => 'Khán giả từ 8 tuổi trở lên. Vui lòng mặc trang phục lịch sự (Smart Casual hoặc Formal).',
                        'checkin' => 'Xuất trình vé điện tử tại cửa soát vé sảnh chính. Khán giả đến trễ sau 19:30 sẽ phải chờ hết phần 1.',
                        'allowed' => 'Được mang điện thoại (vui lòng tắt chuông và không bật đèn flash).',
                        'prohibited' => 'Nghiêm cấm quay phim, chụp ảnh có flash, mang đồ ăn thức uống vào khán phòng.',
                        'refund' => 'Vé không hoàn hủy sau khi đã thanh toán thành công.'
                    ]
                ]
            ],
            [
                3, 'event', 'Vietnam Tech Summit & Global AI Expo 2026', 'vietnam-tech-summit-ai-expo-2026', 3,
                'Diễn đàn trí tuệ nhân tạo và công nghệ tương lai hàng đầu với hơn 50 diễn giả chuyên gia từ Google, OpenAI, Microsoft và Nvidia.',
                480, 300000, false,
                [
                    'venue_name' => 'Trung Tâm Hội Nghị & Triển Lãm SECC Hall A',
                    'venue_address' => '799 Nguyễn Văn Linh, Tân Phú, Quận 7, TP.HCM',
                    'venue_gates' => 'Cổng A1 (Khu Hội Nghị Chính), Cổng B2 (Khu Triển Lãm AI Expo)',
                    'parking_info' => 'Bãi giữ xe tầng hầm SECC và bãi ngoài trời (sức chứa hơn 2.000 xe hơi và xe máy).',
                    'participants_title' => 'Dàn Diễn Giả Keynote Speakers & Lãnh Đạo Công Nghệ Toàn Cầu',
                    'participants_summary' => 'Quy tụ các nhà khoa học AI và Giám đốc kỹ thuật từ Google, OpenAI, Microsoft, Nvidia & FPT',
                    'host_mc' => 'TS. Nguyễn Nhật Quang (Phó Chủ Tịch VINASA)',
                    'special_guests' => '50+ CTOs, Tech Leaders và Quỹ đầu tư mạo hiểm Silicon Valley & Đông Nam Á',
                    'timeline' => [
                        ['time' => '08:00 - 09:00', 'title' => 'Check-in & Nhận Thẻ Đại Biểu', 'desc' => 'Nhận thẻ đeo thông minh NFC, tài liệu hội nghị và teabreak sáng.'],
                        ['time' => '09:00 - 12:00', 'title' => 'Phiên Toàn Thể: Kỷ Nguyên Trí Tuệ Nhân Tạo 2026', 'desc' => 'Keynote từ các giám đốc công nghệ hàng đầu Google, OpenAI và Microsoft.'],
                        ['time' => '12:00 - 13:30', 'title' => 'Buffet Lunch & Networking Doanh Nghiệp', 'desc' => 'Giao lưu kết nối kinh doanh tại sảnh VIP Lounge.'],
                        ['time' => '13:30 - 17:00', 'title' => 'Các Phiên Chuyên Đề & Trải Nghiệm AI Expo', 'desc' => 'Demo sản phẩm công nghệ thực tế và tọa đàm chuyên sâu.']
                    ],
                    'lineup' => [
                        ['name' => 'Dr. Andrew Ng', 'role' => 'AI Scientist & Keynote Speaker', 'tag' => 'Founder DeepLearning.AI', 'badge' => 'Keynote Pioneer'],
                        ['name' => 'Brad Lightcap', 'role' => 'Chief Operating Officer', 'tag' => 'OpenAI Executive', 'badge' => 'OpenAI Leader'],
                        ['name' => 'Jeff Dean Rep', 'role' => 'Chief Scientist', 'tag' => 'Google DeepMind', 'badge' => 'AI Research Head'],
                        ['name' => 'Jensen Huang Rep', 'role' => 'VP Developer Ecosystem', 'tag' => 'Nvidia GPU Computing', 'badge' => 'Nvidia Keynote'],
                        ['name' => 'Trương Gia Bình', 'role' => 'Chủ Tịch HĐQT Tập Đoàn', 'tag' => 'FPT Corporation', 'badge' => 'VN Tech Titan']
                    ],
                    'organizers' => [
                        'lead' => 'Hiệp Hội Phần Mềm & Dịch Vụ CNTT Việt Nam (VINASA)',
                        'sponsor' => 'Nvidia, Google Cloud & FPT Telecom',
                        'media' => 'VnExpress & Forbes Vietnam'
                    ],
                    'entry_policy' => [
                        'age' => 'Dành cho chuyên gia, kỹ sư công nghệ, doanh nghiệp và sinh viên CNTT.',
                        'checkin' => 'Quét mã QR trên vé để in thẻ đại biểu tự động tại quầy Kiosk Check-in.',
                        'allowed' => 'Được mang laptop, máy tính bảng, điện thoại và tài liệu cá nhân.',
                        'prohibited' => 'Không hút thuốc trong hội trường, không mang vũ khí và vật liệu dễ cháy nổ.',
                        'refund' => 'Hỗ trợ chuyển nhượng thông tin thẻ đại biểu trước 48 giờ diễn ra sự kiện.'
                    ]
                ]
            ],
            [
                4, 'event', 'Triển Lãm Đa Giác Quan: Van Gogh & Impressionism Experience', 'trien-lam-nghe-thuat-van-gogh-impressionism', 4,
                'Không gian ánh sáng tương tác đa giác quan 360 độ đưa người xem đắm chìm vào thế giới hội họa kiệt tác của Vincent Van Gogh.',
                90, 150000, false,
                [
                    'venue_name' => 'Art Light Museum Saigon',
                    'venue_address' => 'Tầng 4, TTTM Gigamall, 240-242 Phạm Văn Đồng, TP. Thủ Đức, TP.HCM',
                    'venue_gates' => 'Cổng Trải Nghiệm Tầng 4',
                    'parking_info' => 'Bãi giữ xe Gigamall (miễn phí 2 giờ đầu khi có hóa đơn vé).',
                    'participants_title' => 'Tác Giả Kiệt Tác, Giám Tuyển Nghệ Thuật & Đội Ngũ Kỹ Thuật 3D Mapping',
                    'participants_summary' => 'Tôn vinh di sản hội họa Vincent Van Gogh với công nghệ thực tế ảo đa giác quan Grande Experiences',
                    'host_mc' => 'Giám tuyển Nghệ thuật TS. Trần Hậu Yên Thế',
                    'special_guests' => 'Bảo tàng Mỹ thuật TP.HCM & Hiệp hội Nghệ thuật Đương đại Quốc tế',
                    'timeline' => [
                        ['time' => '09:00 - 21:00', 'title' => 'Mở Cửa Tự Do Theo Khung Giờ', 'desc' => 'Thời gian tham quan khuyến nghị: 60 - 90 phút/lượt.']
                    ],
                    'lineup' => [
                        ['name' => 'Vincent Van Gogh Heritage', 'role' => 'Danh Họa Hậu Ấn Tượng', 'tag' => 'Kiệt Tác Hà Lan 1853-1890', 'badge' => 'Master Impressionist'],
                        ['name' => 'Grande Experiences', 'role' => 'Đơn Vị Sáng Tạo Toàn Cầu', 'tag' => 'Melbourne Multi-Sensory', 'badge' => 'Sensory Creator'],
                        ['name' => 'TS. Trần Hậu Yên Thế', 'role' => 'Giám Tuyển Nghệ Thuật', 'tag' => 'Cố Vấn Lịch Sử Nghệ Thuật', 'badge' => 'Chief Curator'],
                        ['name' => 'Art Stage Media', 'role' => 'Đội Ngũ Kỹ Sư Ánh Sáng', 'tag' => '3D Projection Mapping', 'badge' => 'Visual Engineers']
                    ],
                    'organizers' => [
                        'lead' => 'Bảo Tàng Nghệ Thuật Số & TicketBox Vietnam',
                        'sponsor' => 'Canon Vietnam & Epson Projectors',
                        'media' => 'Art & Life Vietnam'
                    ],
                    'entry_policy' => [
                        'age' => 'Phù hợp mọi lứa tuổi. Trẻ em dưới 1 mét miễn phí vé.',
                        'checkin' => 'Quét vé vào cổng trực tiếp tại cửa bảo tàng theo khung giờ đã chọn.',
                        'allowed' => 'Thoải mái chụp ảnh, quay video không dùng chân máy lớn.',
                        'prohibited' => 'Không chạm vào hệ thống máy chiếu, không mang đồ ăn vào khu trưng bày.',
                        'refund' => 'Có thể đổi khung giờ tham quan trước 24 giờ.'
                    ]
                ]
            ],
            [
                5, 'event', 'Fan Meeting 2026: Gặp Gỡ & Ký Tặng Dàn Cast Running Man VN', 'fan-meeting-running-man-vn-2026', 5,
                'Đêm fan meeting độc quyền giao lưu, ký tặng fansign 1:1, biểu diễn âm nhạc acoustic và chụp ảnh polaroid cùng thần tượng.',
                150, 220000, false,
                [
                    'venue_name' => 'Nhà Thi Đấu Thể Dục Thể Thao Nguyễn Du',
                    'venue_address' => '116 Nguyễn Du, Phường Bến Thành, Quận 1, TP.HCM',
                    'venue_gates' => 'Cổng 1 (Ký Tặng Fansign), Cổng 2 (Khán Giả Chung)',
                    'parking_info' => 'Bãi giữ xe Nhà thi đấu Nguyễn Du và Công viên Tao Đàn.',
                    'participants_title' => 'Dàn Cast Running Man Vietnam & Khách Mời Giao Lưu Fansign Độc Quyền',
                    'participants_summary' => 'Buổi gặp gỡ đặc biệt của trọn bộ dàn cast chính: Lan Ngọc, Ngô Kiến Huy, Jun Phạm, Liên Bỉnh Phát, Trương Thế Vinh, Thúy Ngân',
                    'host_mc' => 'MC Quang Bảo (Dẫn dắt giao lưu fansign & minigame)',
                    'special_guests' => 'Khách Mời Thần Tượng Âm Nhạc Hàn Quốc (Secret Guest)',
                    'timeline' => [
                        ['time' => '16:00 - 17:30', 'title' => 'Ký Tặng Fansign 1:1', 'desc' => 'Dành cho hạng vé VIP Fansign lên sân khấu nhận chữ ký trực tiếp.'],
                        ['time' => '18:00', 'title' => 'Mở Cửa Hội Trường', 'desc' => 'Đón toàn bộ khán giả ổn định vị trí.'],
                        ['time' => '19:00 - 21:30', 'title' => 'Chương Trình Giao Lưu & Mini Game', 'desc' => 'Biểu diễn văn nghệ, bốc thăm may mắn và chụp ảnh lưu niệm.']
                    ],
                    'lineup' => [
                        ['name' => 'Ninh Dương Lan Ngọc', 'role' => 'Nữ Thần Running Man', 'tag' => 'Cast Chính / Fansign', 'badge' => 'Nữ Thần Cơ Hội'],
                        ['name' => 'Ngô Kiến Huy', 'role' => 'Ca Sĩ / MC', 'tag' => 'Cast Chính / Thỏ Đen', 'badge' => 'Thỏ Đen May Mắn'],
                        ['name' => 'Jun Phạm', 'role' => 'Ca Sĩ / Đạo Diễn', 'tag' => 'Cast Chính / Thỏ Trắng', 'badge' => 'Thỏ Trắng Thông Thái'],
                        ['name' => 'Liên Bỉnh Phát', 'role' => 'Diễn Viên Điện Ảnh', 'tag' => 'Cast Chính / Mầm Non', 'badge' => 'Mầm Non Giải Trí'],
                        ['name' => 'Trương Thế Vinh', 'role' => 'Ca Sĩ / Diễn Viên', 'tag' => 'Cast Chính / Voi Biển', 'badge' => 'Voi Biển Sức Mạnh'],
                        ['name' => 'Thúy Ngân', 'role' => 'Diễn Viên / Người Mẫu', 'tag' => 'Cast Chính / Hoa Săn Mồi', 'badge' => 'Hoa Săn Mồi']
                    ],
                    'organizers' => [
                        'lead' => 'Running Man VN Fanclub & Đông Tây Promotion',
                        'sponsor' => 'Shopee & Trà Sữa Gong Cha',
                        'media' => 'Kênh 14 & Yeah1 Music'
                    ],
                    'entry_policy' => [
                        'age' => 'Dành cho mọi lứa tuổi từ 10 tuổi trở lên.',
                        'checkin' => 'Xuất trình vé E-Ticket tại cửa để nhận thẻ đeo Fansign và quà tặng.',
                        'allowed' => 'Được mang banner cổ vũ khổ A3, lightstick, quà lưu niệm tặng nghệ sĩ.',
                        'prohibited' => 'Không chen lấn, xô đẩy, không mang đồ vật sắc nhọn.',
                        'refund' => 'Vé không áp dụng hoàn hủy sau khi phát hành.'
                    ]
                ]
            ],
            [
                6, 'event', 'Vở Nhạc Kịch Broadway: Những Người Khốn Khổ (Les Misérables VN)', 'nhac-kich-les-miserables-vn', 6,
                'Tác phẩm nhạc kịch kinh điển thế giới được chuyển soạn công phu với dàn hợp xướng 80 người và phục trang hoàng gia lộng lẫy.',
                165, 200000, true,
                [
                    'venue_name' => 'Nhà Hát Hòa Bình Main Hall',
                    'venue_address' => '240 đường 3 Tháng 2, Phường 12, Quận 10, TP.HCM',
                    'venue_gates' => 'Cổng Chính Đường 3/2 (Tầng Trệt & Lầu 1)',
                    'parking_info' => 'Bãi giữ xe Nhà hát Hòa Bình (sức chứa 1.000 xe máy, 200 ô tô).',
                    'participants_title' => 'Dàn Diễn Viên Nhạc Kịch Quốc Gia, Đạo Diễn & Dàn Hợp Xướng 80 Người',
                    'participants_summary' => 'Dàn dựng bởi Nhà hát Nhạc Vũ Kịch Việt Nam (VNOB) cùng các nghệ sĩ Opera Broadway hàng đầu',
                    'host_mc' => 'NSND Triệu Trung Kiên (Đạo Diễn Dàn Dựng)',
                    'special_guests' => 'Dàn Hợp Xướng Quốc Gia 60 Thành Viên & Vũ Đoàn Nhạc Vũ Kịch VNOB',
                    'timeline' => [
                        ['time' => '18:30 - 19:15', 'title' => 'Đón Khách & Check-in', 'desc' => 'Ổn định chỗ ngồi trước 19:25.'],
                        ['time' => '19:30 - 22:15', 'title' => 'Biểu Diễn Vở Nhạc Kịch', 'desc' => 'Bao gồm 15 phút giải lao giữa 2 màn kịch.']
                    ],
                    'lineup' => [
                        ['name' => 'Đào Tố Loan', 'role' => 'Nghệ Sĩ Opera Soprano', 'tag' => 'Vai Fantine', 'badge' => 'Lead Soprano'],
                        ['name' => 'Nguyễn Khắc Hòa', 'role' => 'Nghệ Sĩ Opera Baritone', 'tag' => 'Vai Jean Valjean', 'badge' => 'Lead Character'],
                        ['name' => 'Đào Mác', 'role' => 'Nghệ Sĩ Ưu Tú', 'tag' => 'Vai Thanh Tra Javert', 'badge' => 'Antagonist Lead'],
                        ['name' => 'Trần Nhật Minh', 'role' => 'Chỉ Huy Âm Nhạc', 'tag' => 'Music Director', 'badge' => 'Conductor'],
                        ['name' => 'Dàn Diễn Viên & Hợp Xướng VNOB', 'role' => 'Đoàn Nhạc Kịch Quốc Gia', 'tag' => '80 Diễn Viên & Hợp Xướng', 'badge' => 'Full Ensemble']
                    ],
                    'organizers' => [
                        'lead' => 'Nhà Hát Nhạc Vũ Kịch Việt Nam (VNOB)',
                        'sponsor' => 'Bộ Văn Hóa Thể Thao & Du Lịch',
                        'media' => 'VTV1 & Báo Tuổi Trẻ'
                    ],
                    'entry_policy' => [
                        'age' => 'Từ 10 tuổi trở lên.',
                        'checkin' => 'Quét mã QR tại cửa theo đúng số ghế in trên vé.',
                        'allowed' => 'Trang phục lịch sự, tắt chuông điện thoại.',
                        'prohibited' => 'Cấm quay phim toàn bộ vở diễn, cấm mang đồ ăn vào rạp.',
                        'refund' => 'Không áp dụng hoàn trả vé.'
                    ]
                ]
            ],
            [
                7, 'event', 'Saigon Autumn Music & Light Festival 2026', 'saigon-autumn-music-light-festival-2026', 7,
                'Lễ hội âm nhạc ngoài trời kết hợp trình diễn nghệ thuật ánh sáng 3D mapping quy mô 20.000 khán giả bên bờ sông Sài Gòn.',
                360, 180000, false,
                [
                    'venue_name' => 'Công Viên Bờ Sông Sài Gòn (Thủ Thiêm Arena)',
                    'venue_address' => 'Đường Trần Bạch Đằng, Khu Đô Thị Mới Thủ Thiêm, TP. Thủ Đức, TP.HCM',
                    'venue_gates' => 'Cổng 1 (Đường Mai Chí Thọ), Cổng 2 (Cầu Ba Son)',
                    'parking_info' => 'Bãi giữ xe Cầu Ba Son và Khu vực Quảng trường Sáng Tạo.',
                    'participants_title' => 'Headliners, Dàn Nghệ Sĩ Hip-hop, Indie & Top EDM DJs',
                    'participants_summary' => 'Lễ hội âm nhạc quy tụ 20+ nghệ sĩ hàng đầu sân khấu ngoài trời Thủ Thiêm Arena',
                    'host_mc' => 'MC Hype Goku & VJ Dustin Phúc Nguyễn',
                    'special_guests' => 'Top DJ Quốc Tế từ Ultra Music Festival & Vũ Đoàn Ánh Sáng Laser 3D',
                    'timeline' => [
                        ['time' => '14:00', 'title' => 'Mở Cổng Lễ Hội (Gates Open)', 'desc' => 'Khu ẩm thực Foodtruck, minigame & DJ Warm-up.'],
                        ['time' => '17:00 - 23:00', 'title' => 'Mega Music Stage & Light Show', 'desc' => 'Dàn nghệ sĩ Indie, Hip-hop & EDM bùng nổ.']
                    ],
                    'lineup' => [
                        ['name' => 'Suboi', 'role' => 'Rapper / Headliner', 'tag' => 'Queen of Hip-hop', 'badge' => 'Main Headliner'],
                        ['name' => 'Vũ.', 'role' => 'Indie Artist / Singer', 'tag' => 'Hoàng Tử Indie', 'badge' => 'Acoustic Headliner'],
                        ['name' => 'DJ Hoaprox', 'role' => 'Music Producer / DJ', 'tag' => 'Top EDM Asia', 'badge' => 'EDM Master'],
                        ['name' => 'Chillies Band', 'role' => 'Indie Pop Band', 'tag' => 'Band Nhạc Khách Mời', 'badge' => 'Live Band'],
                        ['name' => 'Tlinh', 'role' => 'Rapper / Singer', 'tag' => 'Gen Z Hit Maker', 'badge' => 'Special Performer'],
                        ['name' => 'Low G', 'role' => 'Rapper', 'tag' => 'Hip-hop Underground', 'badge' => 'Flow Master']
                    ],
                    'organizers' => [
                        'lead' => 'Saigon Music Festival Co. & TicketBox',
                        'sponsor' => 'Heineken & Grab Vietnam',
                        'media' => 'Zing MP3 & Billboard VN'
                    ],
                    'entry_policy' => [
                        'age' => 'Dành cho khán giả từ 16 tuổi trở lên.',
                        'checkin' => 'Đổi vòng tay RFID tại cổng trước khi vào khu vực sân khấu.',
                        'allowed' => 'Thoải mái mang túi xách nhỏ, sạc pin, áo mưa cá nhân.',
                        'prohibited' => 'Cấm mang rượu bia ngoài, vật sắc nhọn, pháo sáng.',
                        'refund' => 'Sự kiện diễn ra bất kể thời tiết nắng mưa, không hoàn vé.'
                    ]
                ]
            ],
            [
                8, 'event', 'Masterclass: Nghệ Thuật Kể Chuyện & Sáng Tạo Phim Bằng AI', 'masterclass-sang-tao-phim-ai-2026', 8,
                'Buổi workshop thực hành 1 ngày cùng các đạo diễn và giám đốc sáng tạo hàng đầu về ứng dụng AI tạo sinh trong truyền thông.',
                240, 350000, false,
                [
                    'venue_name' => 'Không Gian Sáng Tạo Toong Co-working Space',
                    'venue_address' => '126 Nguyễn Thị Minh Khai, Phường 6, Quận 3, TP.HCM',
                    'venue_gates' => 'Sảnh Chính Tầng 3',
                    'parking_info' => 'Bãi giữ xe tòa nhà Toong (tầng hầm).',
                    'participants_title' => 'Giảng Viên Trưởng, Đạo Diễn & Chuyên Gia Prompt AI Quốc Tế',
                    'participants_summary' => 'Dẫn dắt bởi Đạo diễn Charlie Nguyễn cùng Chuyên gia AI Video Generative hàng đầu',
                    'host_mc' => 'Giám đốc Điều hành Toong Co-working',
                    'special_guests' => 'Hội Điện Ảnh TP.HCM & Học Viện Kỹ Xảo Điện Ảnh Sài Gòn (Cấp Chứng Nhận)',
                    'timeline' => [
                        ['time' => '08:30 - 09:00', 'title' => 'Đón Học Viên & Teabreak', 'desc' => 'Cài đặt phần mềm và nhận tài liệu độc quyền.'],
                        ['time' => '09:00 - 12:00', 'title' => 'Phần 1: Kỹ Thuật Viết Kịch Bản & Prompt AI Storytelling', 'desc' => 'Thực hành tương tác cùng chuyên gia.'],
                        ['time' => '13:30 - 17:00', 'title' => 'Phần 2: Sản Xuất Video AI & Chấm Điểm Dự Án', 'desc' => 'Trao chứng nhận tốt nghiệp Masterclass.']
                    ],
                    'lineup' => [
                        ['name' => 'Đạo Diễn Charlie Nguyễn', 'role' => 'Đạo Diễn Triệu Đô / Giảng Viên', 'tag' => 'Film Director & Writer', 'badge' => 'Lead Instructor'],
                        ['name' => 'Hoàng Nam AI Master', 'role' => 'Chuyên Gia Midjourney & Runway', 'tag' => 'Top AI Video Creator', 'badge' => 'Prompt Master'],
                        ['name' => 'Phan Gia Nhật Linh', 'role' => 'Đạo Diễn Khách Mời', 'tag' => 'Giám Khảo Chấm Điểm', 'badge' => 'Guest Director'],
                        ['name' => 'Đội Ngũ Mentor Trợ Giảng', 'role' => '5 Kỹ Sư Prompting AI', 'tag' => 'Hỗ Trợ Thực Hành 1:1', 'badge' => 'Hands-on Mentors']
                    ],
                    'organizers' => [
                        'lead' => 'Học Viện Nghệ Thuật Số Sài Gòn & TicketBox',
                        'sponsor' => 'Adobe Creative Cloud & Wacom Vietnam',
                        'media' => 'RGB.vn & DesignerVN'
                    ],
                    'entry_policy' => [
                        'age' => 'Dành cho người làm sáng tạo, marketer, nhà làm phim từ 18 tuổi.',
                        'checkin' => 'Check-in bằng vé điện tử để nhận trọn bộ tài liệu khóa học.',
                        'allowed' => 'Học viên cần mang theo laptop cá nhân để thực hành.',
                        'prohibited' => 'Không phát tán tài liệu nội bộ ra ngoài khi chưa được phép.',
                        'refund' => 'Hoàn 100% học phí nếu báo trước 7 ngày.'
                    ]
                ]
            ],
        ];

        return collect($rows)->map(function ($r) use ($categories) {
            [$id, $type, $title, $slug, $categoryId, $description, $duration, $basePrice, $isSeatedConcert, $bizInfo] = $r;

            return (object) array_merge([
                'id' => $id,
                'type' => $type,
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'poster_path' => "https://picsum.photos/seed/{$slug}/480/720",
                'duration_minutes' => $duration,
                'base_price' => $basePrice ?? 180000,
                'status' => 'published',
                'category' => $categories[$categoryId] ?? (object) ['id' => $categoryId, 'name' => 'Sự kiện'],
                'is_seated_concert' => $isSeatedConcert ?? false,
            ], $bizInfo ?? []);
        })->all();
    }

    public static function events(): array
    {
        return self::movies();
    }

    public static function eventBySlug(string $slug): ?object
    {
        return collect(self::events())->firstWhere('slug', $slug);
    }

    public static function movieBySlug(string $slug): ?object
    {
        return self::eventBySlug($slug);
    }

    public static function rooms(): array
    {
        return [
            (object) ['id' => 1, 'name' => 'Sân Vận Động Quân Khu 7 (SVĐ QK7 Arena)', 'address' => 'Số 202 Hoàng Văn Thụ, Phường 9, Quận Phú Nhuận, TP.HCM', 'latitude' => 10.8005, 'longitude' => 106.6658, 'capacity' => 20000],
            (object) ['id' => 2, 'name' => 'Khán Phòng Hòa Nhạc Saigon Grand Opera House', 'address' => 'Nhà Hát Thành Phố, 07 Công Trường Lam Sơn, Bến Nghé, Quận 1, TP.HCM', 'latitude' => 10.7766, 'longitude' => 106.7032, 'capacity' => 800],
            (object) ['id' => 3, 'name' => 'Trung Tâm Hội Nghị & Triển Lãm SECC Hall A', 'address' => '799 Nguyễn Văn Linh, Tân Phú, Quận 7, TP.HCM', 'latitude' => 10.7302, 'longitude' => 106.7218, 'capacity' => 3000],
            (object) ['id' => 4, 'name' => 'Nhà Hát Hòa Bình Main Hall', 'address' => '240 đường 3 Tháng 2, Phường 12, Quận 10, TP.HCM', 'latitude' => 10.7712, 'longitude' => 106.6745, 'capacity' => 2500],
        ];
    }

    public static function showtimesForEvent(int $eventId): array
    {
        $rooms = collect(self::rooms())->keyBy('id');
        $event = collect(self::events())->firstWhere('id', $eventId);
        $basePrice = $event->base_price ?? 180000;
        $roomId = ($eventId % 4 === 0) ? 4 : (($eventId % 3 === 0) ? 3 : (($eventId % 2 === 0) ? 2 : 1));

        return [
            (object) [
                'id' => $eventId * 10 + 1,
                'event_id' => $eventId,
                'movie_id' => $eventId,
                'room' => $rooms[$roomId] ?? $rooms[1],
                'start_time' => now()->addDay()->setTime(19, 30),
                'base_price' => $basePrice,
            ],
            (object) [
                'id' => $eventId * 10 + 2,
                'event_id' => $eventId,
                'movie_id' => $eventId,
                'room' => $rooms[$roomId] ?? $rooms[1],
                'start_time' => now()->addDay()->setTime(20, 30),
                'base_price' => $basePrice + 20000,
            ],
            (object) [
                'id' => $eventId * 10 + 3,
                'event_id' => $eventId,
                'movie_id' => $eventId,
                'room' => $rooms[$roomId === 4 ? 1 : $roomId + 1] ?? $rooms[1],
                'start_time' => now()->addDays(3)->setTime(18, 30),
                'base_price' => $basePrice,
            ],
        ];
    }

    public static function showtimesForMovie(int $movieId): array
    {
        return self::showtimesForEvent($movieId);
    }

    public static function showtimeById(int $showtimeId): ?object
    {
        $eventId = intdiv($showtimeId, 10);

        return collect(self::showtimesForEvent($eventId))->firstWhere('id', $showtimeId);
    }

    public static function ticketTiers(int $basePrice = 180000, bool $isSeatedConcert = true): array
    {
        return TicketTiers::for($basePrice, $isSeatedConcert);
    }

    /**
     * Concert Stadium Seat Matrix with Large Capacity (140+ Seats & Blocks):
     */
    public static function seatsForShowtime(int $showtimeId): array
    {
        $movie = collect(self::movies())->firstWhere('id', intdiv($showtimeId, 10)) ?? self::movies()[0];
        $basePrice = $movie->base_price ?? 180000;
        $isSeatedConcert = $movie->is_seated_concert ?? true;
        $tiers = self::ticketTiers($basePrice, $isSeatedConcert);

        // If not a seated concert, return empty seats array (direct ticket tier selection)
        if (! $isSeatedConcert) {
            return [];
        }

        $stadiumSections = [
            // Sàn Catwalk Trung Tâm B-Stage (2 Hàng SVIP)
            ['block' => 'SVIP-1', 'row' => 'SVIP-A', 'count' => 12, 'type' => 'svip_diamond', 'label' => 'SVIP B-Stage Hàng A', 'sector' => 'floor'],
            ['block' => 'SVIP-2', 'row' => 'SVIP-B', 'count' => 12, 'type' => 'svip_diamond', 'label' => 'SVIP B-Stage Hàng B', 'sector' => 'floor'],

            // Sàn Ghế VIP Trung Tâm (4 Hàng VIP Floor)
            ['block' => 'FL-A1', 'row' => 'FL-1', 'count' => 14, 'type' => 'vip_gold', 'label' => 'VIP Floor Hàng 1', 'sector' => 'floor'],
            ['block' => 'FL-A2', 'row' => 'FL-2', 'count' => 14, 'type' => 'vip_gold', 'label' => 'VIP Floor Hàng 2', 'sector' => 'floor'],
            ['block' => 'FL-B1', 'row' => 'FL-3', 'count' => 14, 'type' => 'vip_gold', 'label' => 'VIP Floor Hàng 3', 'sector' => 'floor'],
            ['block' => 'FL-B2', 'row' => 'FL-4', 'count' => 14, 'type' => 'vip_gold', 'label' => 'VIP Floor Hàng 4', 'sector' => 'floor'],

            // Khán đài Cánh Trái (North Stand - 2 Hàng)
            ['block' => 'STAND-C1', 'row' => 'C1', 'count' => 12, 'type' => 'cat2_wings', 'label' => 'Khán Đài C Hàng 1 (Cánh Trái)', 'sector' => 'left_stand'],
            ['block' => 'STAND-C2', 'row' => 'C2', 'count' => 12, 'type' => 'cat2_wings', 'label' => 'Khán Đài C Hàng 2 (Cánh Trái)', 'sector' => 'left_stand'],

            // Khán đài Trung Tâm A (West Stand - 3 Hàng Lớn Tầng 1)
            ['block' => 'STAND-A1', 'row' => 'A1', 'count' => 16, 'type' => 'cat1_stand', 'label' => 'Khán Đài A Hàng 1 (Trung Tâm Tầng 1)', 'sector' => 'center_stand'],
            ['block' => 'STAND-A2', 'row' => 'A2', 'count' => 16, 'type' => 'cat1_stand', 'label' => 'Khán Đài A Hàng 2 (Trung Tâm Tầng 1)', 'sector' => 'center_stand'],
            ['block' => 'STAND-A3', 'row' => 'A3', 'count' => 16, 'type' => 'cat1_stand', 'label' => 'Khán Đài A Hàng 3 (Trung Tâm Tầng 1)', 'sector' => 'center_stand'],

            // Khán đài Trung Tâm B (West Upper Stand - 2 Hàng Tầng 2)
            ['block' => 'STAND-B1', 'row' => 'B1', 'count' => 14, 'type' => 'cat1_stand', 'label' => 'Khán Đài B Hàng 1 (Trung Tâm Tầng 2)', 'sector' => 'upper_stand'],
            ['block' => 'STAND-B2', 'row' => 'B2', 'count' => 14, 'type' => 'cat1_stand', 'label' => 'Khán Đài B Hàng 2 (Trung Tâm Tầng 2)', 'sector' => 'upper_stand'],

            // Khán đài Cánh Phải (South Stand - 2 Hàng)
            ['block' => 'STAND-D1', 'row' => 'D1', 'count' => 12, 'type' => 'cat2_wings', 'label' => 'Khán Đài D Hàng 1 (Cánh Phải)', 'sector' => 'right_stand'],
            ['block' => 'STAND-D2', 'row' => 'D2', 'count' => 12, 'type' => 'cat2_wings', 'label' => 'Khán Đài D Hàng 2 (Cánh Phải)', 'sector' => 'right_stand'],

            // Skybox VIP Suites (8 Buồng Đôi Tầng Thượng)
            ['block' => 'SKYBOX', 'row' => 'SB', 'count' => 8, 'type' => 'skybox_suite', 'label' => 'Skybox VIP Suites (Tầng Thượng)', 'sector' => 'skybox'],
        ];

        $bookedIndexes = [3, 4, 15, 26, 38, 49, 62, 75, 88, 102, 115, 126, 140, 155, 172, 188];
        $heldIndexes = [8, 22, 54, 82, 110, 134, 166];

        $seats = [];
        $index = 0;

        foreach ($stadiumSections as $sec) {
            for ($number = 1; $number <= $sec['count']; $number++) {
                $status = 'available';
                if (in_array($index, $bookedIndexes, true)) {
                    $status = 'booked';
                } elseif (in_array($index, $heldIndexes, true)) {
                    $status = 'held';
                }

                $tierInfo = $tiers[$sec['type']] ?? $tiers['cat2_wings'];
                $price = $tierInfo['price'];

                $seats[] = (object) [
                    'id' => $showtimeId * 1000 + $index,
                    'showtime_id' => $showtimeId,
                    'block' => $sec['block'],
                    'sector' => $sec['sector'],
                    'sector_label' => $sec['label'],
                    'row_label' => $sec['row'],
                    'seat_number' => $number,
                    'code' => $sec['row'].'-'.str_pad($number, 2, '0', STR_PAD_LEFT),
                    'type' => $sec['type'],
                    'type_name' => $tierInfo['name'],
                    'type_badge' => $tierInfo['badge'],
                    'type_icon' => $tierInfo['icon'],
                    'gate' => $tierInfo['gate'],
                    'price' => $price,
                    'status' => $status,
                    'perks' => $tierInfo['perks'],
                ];
                $index++;
            }
        }

        return $seats;
    }

    public static function cartItems(): array
    {
        $movie = self::movies()[0];
        $showtime = self::showtimesForMovie($movie->id)[0];

        return [
            (object) [
                'showtime' => $showtime,
                'movie' => $movie,
                'seat' => (object) ['row_label' => 'C', 'seat_number' => 4, 'type' => 'normal'],
                'price' => 250000,
            ],
            (object) [
                'showtime' => $showtime,
                'movie' => $movie,
                'seat' => (object) ['row_label' => 'C', 'seat_number' => 5, 'type' => 'normal'],
                'price' => 250000,
            ],
        ];
    }

    public static function bookingHistory(): array
    {
        $movie = self::movies()[0];
        $showtime = self::showtimesForMovie($movie->id)[0];

        $movie2 = self::movies()[1];
        $showtime2 = self::showtimesForMovie($movie2->id)[0];

        return [
            (object) [
                'id' => 1,
                'booking_code' => 'TBX-89214',
                'movie' => $movie,
                'showtime' => $showtime,
                'seats' => ['D2', 'D3'],
                'total_price' => 500000,
                'status' => 'confirmed',
                'created_at' => now()->subDays(2),
            ],
            (object) [
                'id' => 2,
                'booking_code' => 'TBX-77102',
                'movie' => $movie2,
                'showtime' => $showtime2,
                'seats' => ['E1'],
                'total_price' => 205000,
                'status' => 'cancelled',
                'created_at' => now()->subDays(6),
            ],
        ];
    }
}

