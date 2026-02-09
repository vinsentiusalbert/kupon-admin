{{-- resources/views/chatime_promo.blade.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $campaign?->campaign_name ?? '' }} Promo</title>
    <style>
        body {
            background-color: #dcdcdc;
            font-family: Arial, sans-serif;
            margin: 0; padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 320px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
            position: relative;
            font-size: 14px;
            transition: transform 0.4s ease;
            will-change: transform;
        }
        body.modal-open .container {
            transform: translateX(-120%);
        }
        .logo {
            padding: 20px 0 10px;
        }
        .logo img {
            height: 36px;
            object-fit: contain;
        }
        .promo-section {
            background: none; /* hapus linear-gradient jika ingin gambar full */
            padding: 0; /* hapus padding agar gambar bisa full */
            border-radius: 24px 24px 0 0;
            position: relative;
            overflow: hidden; /* penting agar border-radius memotong gambar */
        }
        .promo-image {
            width: 100%;
            height: 300px; /* atur tinggi sesuai kebutuhan */
            position: relative;
        }
        .promo-image img {
            width: 100%;
            height: 100%; /* supaya menutupi area promo-section */
            object-fit: fill; /* menjaga proporsi, tapi menutupi seluruh area */
            display: block;
            border-radius: 24px 24px 0 0;
        }
        .promo-text {
            color: #000;
            font-weight: 900;
            font-size: 26px;
            margin-top: 15px;
            font-family: 'Arial Black', Arial, sans-serif;
            letter-spacing: 1px;
            user-select: none;
        }
        .countdown {
            display: flex;
            justify-content: center;
            margin: 15px 0 25px;
            gap: 10px;
        }
        .countdown-item {
            background: #ffdede;
            border-radius: 8px;
            padding: 10px 14px;
            width: 58px;
            box-shadow: 0 0 10px #f2a0a0;
            user-select: none;
        }
        .countdown-number {
            font-weight: 700;
            font-size: 24px;
            color: #d53a3a;
        }
        .countdown-label {
            font-size: 11px;
            color: #b64646;
            margin-top: 2px;
            text-transform: capitalize;
        }
        .content-section {
            padding: 5px 25px 17px;
            position: relative;
            background: #fff;
            border-radius: 0 0 24px 24px;
            box-sizing: border-box;
        }
        .location {
            /* margin-top: 50px; */
            font-size: 13px;
            color: #555;
            margin-bottom: 10px;
            user-select: none;
        }
        .location a {
            color: #1a73e8;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-red {
            background: #f53636;
            color: #fff;
            font-weight: 700;
            font-size: 18px;
            padding: 14px 0;
            margin: 10px 0 20px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            box-shadow: 0 0 20px #f53636;
            transition: background-color 0.3s ease;
            width: 100%;
            user-select: none;
        }
        .btn-red:hover {
            background-color: #c12929;
        }
        .terms {
            font-size: 12px;
            color: #555;
            margin-bottom: 10px;
            cursor: pointer;
            text-decoration: underline;
            user-select: none;
        }
        .footer-text {
            font-size: 10px;
            color: #999;
            user-select: none;
        }
        .footer-text a {
            color: #1a73e8;
            text-decoration: none;
        }
        
        .container::before {
            left: -20px;
        }
        .container::after {
            right: -20px;
        }
        .circle-a {
            width: 1.3rem;
            height: 2.3rem;
            border: none;
            background-color: #dcdcdc;
            border-radius: 0rem 50rem 50rem 0rem;
            border-left: #dcdcdc 1px solid;
            border-top: #dcdcdc 1px solid;
            border-bottom: #dcdcdc 1px solid;
            box-shadow: -12px 0 10px 7px rgba(235, 235, 235), -20px 0px 20px 0px rgba(243, 243, 243), -20px 0px 10px 4px rgba(243, 243, 243);
        }
        .d-flex {
            display: flex !important;
        }
        .dashed {
            width: 100%;
            border-top: 5px dashed #dcdcdc;
            opacity: 0.5;
        }
        .align-self-center {
            align-self: center !important;
        }
        .circle-b {
            width: 1.3rem;
            height: 2.3rem;
            background-color: #dcdcdc;
            border-radius: 50rem 0rem 0rem 50rem;
            border-right: #dcdcdc 1px solid;
            border-top: #dcdcdc 1px solid;
            border-left: #dcdcdc 1px solid;
            border-bottom: #dcdcdc 1px solid;
            box-shadow: 12px 0 10px 7px rgba(235, 235, 235), 20px 0px 20px 0px rgba(235, 235, 235), 20px 0px 10px 4px rgba(235, 235, 235);
        }



        .modal {
            position: fixed;
            inset: 0;
            background: #dcdcdc;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .modal.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-box {
            width: 320px;
            background: #fff;
            border-radius: 24px;
            padding: 20px;
            text-align: center;
            transform: translateX(110%);
            transition: transform 0.4s ease;
            will-change: transform;
        }
        .modal.is-open .modal-box {
            transform: translateX(0);
        }

        .modal-logo img {
            height: 40px;
            margin-bottom: 10px;
        }

        .modal-box h3 {
            margin: 10px 0 5px;
            font-size: 18px;
        }

        .subtitle {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
        }

.result-box {
    display: none;
    margin-top: 12px;
    padding: 10px 12px;
    border-radius: 12px;
    background: #e9f9ee;
    color: #1f7a3e;
    font-size: 13px;
    text-align: center;
}
.result-box.is-error {
    background: #fdecec;
    color: #b53a3a;
}
.notif-modal {
    position: fixed;
    inset: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    background: rgba(220, 220, 220, 0.6);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
    z-index: 1000;
}
.notif-modal.is-open {
    opacity: 1;
    pointer-events: auto;
}
.notif-box {
    width: 320px;
    background: #fff;
    border-radius: 24px;
    padding: 16px 16px 18px;
    text-align: center;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    transform: translateY(20px) scale(0.95);
    opacity: 0;
    transition: transform 0.35s ease, opacity 0.35s ease;
}
.notif-modal.is-open .notif-box {
    transform: translateY(0) scale(1);
    opacity: 1;
}
.notif-logo img {
    height: 40px;
    margin: 2px 0 10px;
}
.notif-image {
    width: 100%;
    height: 220px;
    border-radius: 18px;
    background: #f4f4f4;
    display: flex;
    align-items: center;
    justify-content: center;
}
.notif-check {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
    font-weight: 900;
    color: #fff;
}
.notif-check.success {
    background: #27ae60;
    box-shadow: 0 12px 20px rgba(39, 174, 96, 0.35);
}
.notif-check.error {
    background: #e74c3c;
    box-shadow: 0 12px 20px rgba(231, 76, 60, 0.35);
}
.notif-title {
    font-size: 18px;
    font-weight: 800;
    margin: 12px 0 6px;
}
.notif-info {
    font-size: 13px;
    color: #555;
    margin: 0 0 10px;
}
.notif-kv {
    background: #f7f7f7;
    border-radius: 12px;
    padding: 10px 12px;
    font-size: 13px;
    color: #333;
}
        .notif-kv strong {
            display: block;
            font-size: 11px;
            color: #888;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .notif-hint {
            font-size: 12px;
            color: #555;
            margin-top: 10px;
        }
        .notif-btn {
            background: #f53636;
            color: #fff;
            border: none;
            border-radius: 18px;
            padding: 8px 16px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 12px;
    box-shadow: 0 0 14px rgba(245, 54, 54, 0.35);
}

        #outletCode {
            width: 100%;
            max-width: 280px;
            box-sizing: border-box;
            margin: 0 auto 15px;
            font-size: 22px;
            text-align: center;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #ccc;
            letter-spacing: 6px;
        }


        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .keypad button {
            height: 60px;
            font-size: 20px;
            border-radius: 50%;
            border: none;
            background: #f2f2f2;
            cursor: pointer;
        }

        .key-clear {
            background: #1abc9c !important;
            color: #fff;
        }

        .key-submit {
            background: #e74c3c !important;
            color: #fff;
        }

        .back-btn {
            margin-top: 15px;
            font-size: 14px;
            color: #555;
            cursor: pointer;
        }

        .location-list {
            text-align: left;
            margin-top: 10px;
        }
        .location-item {
            background: #fafafa;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }
        .location-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            cursor: pointer;
            user-select: none;
        }
        .location-chevron {
            transition: transform 0.25s ease;
            font-size: 16px;
        }
        .location-item.is-open .location-chevron {
            transform: rotate(180deg);
        }
        .location-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .location-body-inner {
            padding-top: 8px;
            font-size: 12px;
            color: #555;
        }
        .location-body-inner a {
            color: #1a73e8;
            font-weight: 600;
            text-decoration: none;
        }

    </style>
</head>
<body>
    <div class="container" >
        <div class="logo-floating">
            <img src="{{ $logoUrl }}" alt="{{ $campaign?->campaign_name ?? '' }} Logo" style="height:40px;"/>
        </div>
        <div class="promo-section">
            <div class="promo-image">
                <img src="{{ $imageUrl }}" alt="{{ $campaign?->campaign_title ?? 'Promo' }}" />
            </div>
            
        </div>
        <div class="content-section">
            <div class="promo-text">{{ $campaign?->campaign_title ?? 'BUY 1 GET 1' }}</div>
            <div class="countdown" id="countdown">
                <div class="countdown-item">
                    <div class="countdown-number" id="days">0</div>
                    <div class="countdown-label">Day</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-number" id="hours">0</div>
                    <div class="countdown-label">Hours</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-number" id="minutes">0</div>
                    <div class="countdown-label">Minutes</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-number" id="seconds">0</div>
                    <div class="countdown-label">Seconds</div>
                </div>
            </div>
        </div> 
        <div class="d-flex">
                <div class="circle-a"></div>
                <div class="align-self-center dashed"></div>
                <div class="circle-b"></div>
            </div>
        <div class="content-section">
            <div class="location">
                Tukar voucher digital ini di outlet <br />
                <a href="javascript:void(0)" onclick="openLocationModal()">📍 Lokasi Penukaran</a>
            </div>
            <button class="btn-red" onclick="openModal()">TUKAR VOUCHER</button>
            <div class="terms">
               <a href="https://myads.telkomsel.com/" target="_blank"> Syarat dan Ketentuan Promo &gt; </a>
            </div>
            <div class="footer-text">
                Mau bisnis anda punya iklan seperti ini ? <a href="https://myads.telkomsel.com/" target="_blank">Click here</a>
            </div>
        </div>
        
            
    </div>





    
    <!-- MODAL LOCATIONS -->
    <div class="modal" id="locationModal">
        <div class="modal-box">
            <div class="modal-logo">
                <img src="{{ $logoUrl }}" alt="{{ $campaign?->campaign_name ?? '' }}">
            </div>

            <h3>Lokasi Penukaran</h3>
            <p class="subtitle">Pilih lokasi, lihat alamat dan link Maps</p>

            <div class="location-list">
                @forelse ($locations as $location)
                    <div class="location-item">
                        <div class="location-header" onclick="toggleLocation(this)">
                            <span>{{ $location->name }}</span>
                            <span class="location-chevron">⌄</span>
                        </div>
                        <div class="location-body">
                            <div class="location-body-inner">
                                <div>{{ $location->addresss }}</div>
                                <div style="margin-top:6px;">
                                    <a href="{{ $location->maps }}" target="_blank" rel="noopener noreferrer">Buka di Google Maps</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="location-item">
                        <div class="location-body-inner">Belum ada lokasi.</div>
                    </div>
                @endforelse
            </div>

            <div class="back-btn" onclick="closeLocationModal()">< Back</div>
        </div>
    </div>


    <!-- MODAL INPUT KODE OUTLET -->
    <div class="modal" id="outletModal">
        <div class="modal-box">
            <div class="modal-logo">
                <img src="{{ $logoUrl }}" alt="{{ $campaign?->campaign_name ?? '' }}">
            </div>

            <h3>Masukkan Code Outlet</h3>
            <p class="subtitle">Silahkan minta kode outlet dari kasir untuk validasi</p>

            <input type="password" id="outletCode" maxlength="6" readonly />

            <div class="keypad">
                <button onclick="pressKey(1)">1</button>
                <button onclick="pressKey(2)">2</button>
                <button onclick="pressKey(3)">3</button>
                <button onclick="pressKey(4)">4</button>
                <button onclick="pressKey(5)">5</button>
                <button onclick="pressKey(6)">6</button>
                <button onclick="pressKey(7)">7</button>
                <button onclick="pressKey(8)">8</button>
                <button onclick="pressKey(9)">9</button>

                <button class="key-clear" onclick="clearKey()">⌫</button>
                <button onclick="pressKey(0)">0</button>
                <button class="key-submit" onclick="submitCode()">✓</button>
            </div>

            <div id="resultBox" class="result-box"></div>

            <div class="back-btn" onclick="closeModal()">< Back</div>
        </div>
    </div>


    <!-- NOTIFICATION MODAL -->
    <div class="notif-modal" id="notifModal">
        <div class="notif-box">
            <div class="notif-logo">
                <img src="{{ $logoUrl }}" alt="{{ $campaign?->campaign_name ?? '' }}">
            </div>
            <div class="notif-image">
                <div id="notifCheck" class="notif-check success">✓</div>
            </div>
            <div id="notifTitle" class="notif-title">Berhasil</div>
            <p id="notifInfo" class="notif-info">Voucher berhasil ditukarkan</p>
            <div class="notif-kv">
                <strong>Voucher</strong>
                <span id="notifVoucher">-</span>
            </div>
            <div class="notif-kv" style="margin-top:8px;">
                <strong>Outlet</strong>
                <span id="notifOutlet">-</span>
            </div>
            <div id="notifHint" class="notif-hint" style="display:none;">
                Redeem Voucher ini dengan akses ke <strong>*200*<span id="notifVoucherInline">-</span>#</strong>
            </div>
            <button class="notif-btn" onclick="closeNotif()">OK</button>
        </div>
    </div>

<script>
    function openModal() {
        document.body.classList.add('modal-open');
        document.getElementById('outletModal').classList.add('is-open');
    }

    function closeModal() {
        document.body.classList.remove('modal-open');
        document.getElementById('outletModal').classList.remove('is-open');
        document.getElementById('outletCode').value = '';
        const resultBox = document.getElementById('resultBox');
        if (resultBox) {
            resultBox.style.display = 'none';
            resultBox.classList.remove('is-error');
            resultBox.textContent = '';
        }
    }

    function openLocationModal() {
        document.body.classList.add('modal-open');
        document.getElementById('locationModal').classList.add('is-open');
    }

    function closeLocationModal() {
        document.body.classList.remove('modal-open');
        document.getElementById('locationModal').classList.remove('is-open');
    }

    function toggleLocation(el) {
        const item = el.closest('.location-item');
        const body = item.querySelector('.location-body');

        if (item.classList.contains('is-open')) {
            body.style.maxHeight = '0px';
            item.classList.remove('is-open');
            return;
        }

        document.querySelectorAll('.location-item.is-open').forEach((openItem) => {
            const openBody = openItem.querySelector('.location-body');
            openBody.style.maxHeight = '0px';
            openItem.classList.remove('is-open');
        });

        item.classList.add('is-open');
        body.style.maxHeight = body.scrollHeight + 'px';
    }

    function openNotif(type, title, text, voucher, outlet) {
        const modal = document.getElementById('notifModal');
        const check = document.getElementById('notifCheck');
        const t = document.getElementById('notifTitle');
        const msg = document.getElementById('notifInfo');
        const v = document.getElementById('notifVoucher');
        const o = document.getElementById('notifOutlet');
        const hint = document.getElementById('notifHint');
        const vInline = document.getElementById('notifVoucherInline');

        if (type === 'error') {
            check.classList.remove('success');
            check.classList.add('error');
            check.textContent = 'X';
        } else {
            check.classList.remove('error');
            check.classList.add('success');
            check.textContent = '✓';
        }

        t.textContent = title;
        msg.textContent = text;
        v.textContent = voucher || '-';
        o.textContent = outlet || '-';
        vInline.textContent = voucher || '-';
        hint.style.display = type === 'error' ? 'none' : 'block';
        modal.classList.add('is-open');
    }

    function closeNotif() {
        document.getElementById('notifModal').classList.remove('is-open');
    }

    function pressKey(num) {
        let input = document.getElementById('outletCode');
        if (input.value.length < 6) {
            input.value += num;
        }
    }

    function clearKey() {
        let input = document.getElementById('outletCode');
        input.value = input.value.slice(0, -1);
    }

    function submitCode() {
        let code = document.getElementById('outletCode').value;
        const resultBox = document.getElementById('resultBox');
        resultBox.classList.remove('is-error');
        resultBox.style.display = 'none';

        // if (code.length < 4) {
        //     alert('Kode outlet belum lengkap');
        //     return;
        // }

        fetch('{{ route('outlet.check') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                outlet_code: code,
                campaign_id: '{{ $campaign?->id }}',
            }),
        })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Kode outlet tidak ditemukan.');
            }
            resultBox.textContent = 'Sukses! Outlet: ' + data.outlet_name + ' | Voucher: ' + data.voucher_code;
            resultBox.classList.remove('is-error');
            resultBox.style.display = 'block';
            openNotif('success', 'Berhasil', 'Voucher berhasil ditukarkan', data.voucher_code, data.outlet_name);
        })
        .catch((err) => {
            resultBox.textContent = err.message;
            resultBox.classList.add('is-error');
            resultBox.style.display = 'block';
            openNotif('error', 'Coba lagi!', 'Kode outlet tidak valid.', '', '');
        });
    }
</script>

    <script>
        // Set waktu akhir promo
        let countdownDate;
        @if ($countdownIso)
            countdownDate = new Date("{{ $countdownIso }}");
        @else
            countdownDate = new Date();
            countdownDate.setHours(countdownDate.getHours() + 28); // fallback 1 hari 4 jam dari sekarang
        @endif

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = countdownDate.getTime() - now;

            if (distance < 0) {
                document.getElementById('countdown').innerHTML = '<div style="color:red; font-weight:bold;">Promo sudah berakhir</div>';
                clearInterval(interval);
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('days').textContent = days;
            document.getElementById('hours').textContent = hours;
            document.getElementById('minutes').textContent = minutes;
            document.getElementById('seconds').textContent = seconds;
        }

        updateCountdown();
        const interval = setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
