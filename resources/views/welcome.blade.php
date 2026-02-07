{{-- resources/views/chatime_promo.blade.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Chatime Promo</title>
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
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.modal-box {
    width: 320px;
    background: #fff;
    border-radius: 24px;
    padding: 20px;
    text-align: center;
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

    </style>
</head>
<body>
    <div class="container" >
        <div class="logo-floating">
            <img src="{{ $logoUrl }}" alt="{{ $campaign?->campaign_name ?? 'Chatime' }} Logo" style="height:40px;"/>
        </div>
        <div class="promo-section">
            <div class="promo-image">
                <img src="{{ $imageUrl }}" alt="{{ $campaign?->campaign_title ?? 'Chatime Promo' }}" />
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
                Tukar kupon digital ini di outlet <strong>Chatime</strong><br />
                <a href="#" target="_blank">📍 Lokasi Penukaran</a>
            </div>
            <button class="btn-red" onclick="openModal()">TUKAR KUPON</button>
            <div class="terms">
                Syarat dan Ketentuan Promo &gt;
            </div>
            <div class="footer-text">
                Mau bisnis anda punya iklan seperti ini ? <a href="https://myads.telkomsel.com/" target="_blank">Click here</a>
            </div>
        </div>
        
            
    </div>





    <!-- MODAL INPUT KODE OUTLET -->
<div class="modal" id="outletModal">
        <div class="modal-box">
            <div class="modal-logo">
                <img src="{{ $logoUrl }}" alt="{{ $campaign?->campaign_name ?? 'Chatime' }}">
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

        <div class="back-btn" onclick="closeModal()">‹ Back</div>
    </div>
</div>

    <script>
    function openModal() {
        document.getElementById('outletModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('outletModal').style.display = 'none';
        document.getElementById('outletCode').value = '';
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
        if (code.length < 4) {
            alert('Kode outlet belum lengkap');
            return;
        }

        alert('Kode outlet: ' + code);
        // TODO: kirim ke backend via AJAX / form submit
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
