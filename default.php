<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to STREAM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --green: #80c0a1;
            --yellow: #F5FCCD;
            --brown: #ff7d66;
            --black: #000038;
            --white: #FFF;
            --blue: #419197;
            --dark-blue: #12486B;
            --orange: orange;
            --grey: #919ea3;
            --dark-grey: #66777F;
        }
        html {
            font-size: 5vh;
        }
        @media screen and (min-height: 400px) {
            html { font-size: 19px; }
        }
        body {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #acd2edff 0%, #d5e7f6ff 100%);
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            opacity: 0;
            animation: pageLoad 0.25s ease-out forwards;
        }

        @keyframes pageLoad {
            to { opacity: 1; }
        }
        .welcome-container {
            text-align: center;
            position: relative;
            margin-top: 8vh; 
            padding-top: 2vh; 
        }
        .fishbowl {
            position: relative;
            width: 15rem;
            height: 15rem;
            margin: 0 auto 2rem auto;
            margin-top: 3vh;
        }
        .fishbowl__background {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 50% 50% 40% 40%;
            background: linear-gradient(transparent 10%, var(--white) 150%);
            border-bottom: 1px solid var(--white);
        }
        .fishbowl:before {
            content: '';
            position: absolute;
            bottom: 9.5rem;
            left: 2rem;
            width: 100%;
            height: 30%;
            border-radius: 50%;
            box-shadow: -4rem 10rem 1rem 0 rgba(0,0,56,0.3);
            transform: rotate(5deg);
        }
        .fishbowl:after {
            content: '';
            position: absolute;
            top: 12.5%;
            left: 2.5%;
            width: 95%;
            height: 85%;
            border-radius: 40%;
            background: linear-gradient(135deg, transparent 50%, var(--white) 150%);
        }
        .fishbowl__bottom {
            position: absolute;
            bottom: 5%;
            left: 17.5%;
            width: 65%;
            height: 20%;
            border-radius: 50%;
            background: linear-gradient(var(--yellow), var(--brown) 200%);
        }
        .fishbowl__decoration {
            position: absolute;
            top: 20%;
            left: 5%;
            width: 90%;
            height: 75%;
        }
        .fishbowl__seaweed {
            position: absolute;
            width: 0;
            height: 0;
            border-left: 0.5rem solid transparent;
            border-right: 0.5rem solid transparent;
            border-bottom: 5rem solid var(--green);
        }
        .fishbowl__seaweed--1 {
            bottom: 15%;
            right: 20%;
            border-bottom: 5rem solid var(--green);
        }
        .fishbowl__seaweed--2 {
            bottom: 10%;
            right: 30%;
            border-bottom: 8rem solid var(--green);
        }
        .fishbowl__seaweed--3 {
            bottom: 15%;
            right: 40%;
            border-bottom: 6rem solid var(--green);
        }
        .fishbowl__water {
            position: absolute;
            bottom: 5%;
            left: 5%;
            width: 90%;
            height: 80%;
            border-radius: 40% 40% 4.8rem 4.8rem;
            transition: height 0.3s ease;
            overflow: hidden;
        }
        .fishbowl__water-color {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: calc(1% * var(--filling, 90));
            background: linear-gradient(transparent -50%, var(--blue) 250%);
            border-radius: 20% 20% 4rem 4rem;
            transition: height 0.5s linear;
        }
        .fishbowl__water-color:after {
            content: '';
            position: absolute;
            top: 0;
            left: 5%;
            width: 90%;
            height: 3rem;
            border-radius: 50%;
            background: linear-gradient(transparent 0%, var(--blue) 250%);
            box-shadow: inset 0 -1px 0 0 rgba(255,255,255,0.5);
        }
        .fishbowl__top {
            position: absolute;
            top: 5%;
            left: 15%;
            width: 70%;
            height: 20%;
            border-radius: 50%;
            box-shadow: 0 2px 2px 3px rgba(255,255,255,0.3); 
        }
        .fishbowl__fish {
            position: absolute;
            bottom: 25%;
            left: 18%;
            width: 2rem;
            height: 1rem;
            border-radius: 50%;
            background: linear-gradient(var(--white) -200%, var(--orange));
            box-shadow: 0 2rem 4px -2px rgba(0,0,56,0.3);
            transition-property: bottom, transform, box-shadow;
            transition-duration: 1s;
            transition-timing-function: ease;
            animation: swimming 5s infinite forwards;
        }
        .fishbowl__fish:after {
            content: '';
            position: absolute;
            top: 15%;
            right: 15%;
            width: 0.25rem;
            height: 0.25rem;
            border-radius: 50%;
            background: radial-gradient(circle at 0 0, var(--white) -100%, var(--dark-blue));
            transition: height 0.5s ease;
        }
        .fishbowl__fish--dying {
            bottom: 10%;
            box-shadow: 0 1rem 4px -2px rgba(0,0,56,0.3);
        }
        .fishbowl__fish--dead {
            animation: dead 2s 2 forwards;
            box-shadow: none;
        }
        .fishbowl__fish--dead:after {
            height: 0.125rem;
        }
        .fishbowl__fish--floating {
            bottom: max(calc(var(--filling, 0) * 1% - 15%), 10%);
            transform: translateX(2.5rem);
            animation: none;
            box-shadow: none;
        }
        .fishbowl__fish--floating:after {
            height: 2px;
        }
        .fishbowl__fish-tail {
            position: absolute;
            top: 0;
            left: -0.75rem;
            width: 0;
            height: 0;
            border-top: 0.5rem solid transparent;
            border-bottom: 0.5rem solid transparent;
            border-left: 0.94rem solid var(--orange);
            box-shadow: 0 2rem 4px -2px rgba(0,0,56,0.3);
            transition: box-shadow 1s ease;
        }
        .fishbowl__pool {
            position: absolute;
            right: 0;
            bottom: -5%;
            width: 50%;
            height: 15%;
            border-radius: 50%;
            background: linear-gradient(var(--white) -100%, var(--blue));
            opacity: 0.5;
        }
        .fishbowl__pool:after {
            content: '';
            position: absolute;
            top: 25%;
            left: 25%;
            width: 50%;
            height: 50%;
            border-right: 2px solid var(--white);
            border-radius: 50%;
            animation: wave 3s infinite;
            opacity: 0.5;
        }
        @keyframes wave {
            from {
                top: 25%;
                left: 25%;
                width: 50%;
                height: 50%;
            }
            to {
                top: 10%;
                left: 10%;
                width: 80%;
                height: 80%;
            }
        }
        .fishbowl__tap {
            position: absolute;
            bottom: 0;
            left: -3rem;
            width: 12rem;
            height: 15.9rem;
            cursor: pointer;
        }
        .fishbowl__tap--active .fishbowl__tap-stream {
            animation: stream 0.5s;
        }
        @keyframes stream {
            0% { height: 0; }
            50% { top: 2rem; height: calc(14rem - var(--filling) * 0.1rem);}
            100% { top: calc(2rem + 14rem - var(--filling) * 0.1rem); height: 0;}
        }
        .fishbowl__tap--active .fishbowl__tap-handle {
            animation: handle 0.5s;
        }
        @keyframes handle {
            from { transform: rotate(45deg);}
            to { transform: rotate(405deg);}
        }
        .fishbowl__tap-base {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 2rem;
            height: 14rem;
            border-radius: 0 0 1.2rem 1.2rem;
            box-shadow: inset -1px -1px 0 0px rgba(255,255,255,0.5);
            background: linear-gradient(var(--grey), var(--dark-grey) 150%);
        }
        .fishbowl__tap-base:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4) 60%, transparent 200%);
            border-radius: 0 0 1.2rem 1.2rem;
        }
        .fishbowl__tap-handle {
            position: absolute;
            left: -1rem;
            bottom: 6rem;
            width: 2rem;
            height: 2rem;
            border-radius: 0.2rem;
            border-top: 1px solid var(--white);
            border-right: 1px solid rgba(255,255,255,0.5);
            background: radial-gradient(circle at 0 0, var(--white) -100%, var(--grey));
            transform: rotate(45deg);
        }
        .fishbowl__tap-handle:after {
            content: '';
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: radial-gradient(circle at 0 0, var(--white) -200%, var(--blue));
        }
        .fishbowl__tap-stream {
            position: absolute;
            top: 2rem;
            left: 6.25rem;
            width: 1.5rem;
            height: 0;
            background: linear-gradient(var(--white) -100%, var(--blue));
            border-radius: 1rem;
            opacity: 0.3;
        }
        .fishbowl__tap-head {
            position: absolute;
            top: 0;
            left: 0;
            width: 4rem;
            height: 0rem;
            border-radius: 50% 50% 0 0;
            border-top: 2rem solid var(--grey); 
            border-left: 2rem solid var(--grey); 
            border-right: 2rem solid var(--grey);
            box-shadow: 1px -1px 0 0 var(--white);
        }
        .fishbowl__tap-head:after {
            content: '';
            position: absolute;
            top: -2rem;
            left: -2rem;
            width: 8rem;
            height: 2rem;
            background: linear-gradient(transparent, rgba(255,255,255,0.4) 60%, transparent 200%);
            border-radius: 50% 50% 0 50%;
        }
        .fishbowl__tap-end {
            position: absolute;
            left: 6rem;
            top: 1.5rem;
            width: 2rem;
            height: 1rem;
            border-radius: 50%;
            background: linear-gradient(var(--white) -70%, var(--grey));
        }
        .fishbowl__tap-text {
            position: absolute;
            top: 4rem;
            left: -6rem;
            color: var(--white);
            font-family: 'Arial', sans-serif;
            font-size: 0.875rem;
        }
        @keyframes swimming {
            0%,100% { transform: translateX(0);}
            22.5% { transform: translateX(2.5rem) skewY(-5deg);}
            45% { transform: translateX(6rem) translateY(-1rem) skewY(5deg);}
            55% { transform: translateX(5rem) translateY(-0.5rem) scaleX(-1);}
            95% { transform: translateX(0) scaleX(-1) skewY(10deg);}
        }
        @keyframes dead {
            0%,100% { transform: translateX(2.5rem);}
            22.5% { transform: translateX(2.5rem) translateY(-1rem) skewY(-5deg);}
            45% { transform: translateX(2.5rem) skewY(5deg);}
            55% { transform: translateX(2.5rem) translateY(-1rem) skewY(-5deg);}
            95% { transform: translateX(2.5rem) skewY(5deg);}
        }
        /* Welcome styles */
        .welcome-title {
            font-size: 2.2rem;
            font-weight: 900;
            color: #1976d2;
            letter-spacing: 2px;
            text-shadow: 2px 2px 0 #bbdefb, 4px 4px 8px rgba(33,150,243,0.18);
            margin-bottom: 0.5rem;
            opacity: 0;
            transform: translateY(6px) scale(0.99);
            animation: titleFadeIn 0.18s 0.04s ease-out forwards;
        }
        @keyframes titleFadeIn {
            to { opacity: 1; transform: translateY(0) scale(1);}
        }
        .welcome-desc {
            font-size: 1.15rem;
            color: #555;
            margin-bottom: 2rem;
            text-shadow: 0 1px 2px #fff;
            opacity: 0;
            transform: translateY(6px) scale(0.99);
            animation: descFadeIn 0.18s 0.06s ease-out forwards;
        }
        @keyframes descFadeIn {
            to { opacity: 1; transform: translateY(0) scale(1);}
        }
        .welcome-desc span {
            display: inline-block;
            opacity: 0;
            transform: scale(0.99);
            animation: highlightFadeIn 0.16s 0.08s ease-out forwards;
        }
        @keyframes highlightFadeIn {
            to { opacity: 1; transform: scale(1);}
        }
        .enter-btn {
            display: inline-block;
            padding: 0.7rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            background: linear-gradient(90deg,#90caf9 0%,#1976d2 100%);
            color: #fff;
            border: none;
            box-shadow: 0 2px 8px rgba(33,150,243,0.10);
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: background 0.2s, box-shadow 0.2s, transform 0.18s;
            margin-top: 1.5rem;
            opacity: 0;
            transform: scale(0.98);
            animation: btnPopIn 0.18s 0.08s ease-out forwards;
        }
        .enter-btn:hover {
            background: linear-gradient(90deg,#1976d2 0%,#90caf9 100%);
            box-shadow: 0 4px 16px rgba(33,150,243,0.18);
            transform: scale(1.03) rotate(-1deg);
        }
        @keyframes btnPopIn {
            0% { opacity: 0; transform: scale(0.92);}
            80% { opacity: 1; transform: scale(1.08);}
            100% { opacity: 1; transform: scale(1);}
        }
        .fade-in {
            opacity: 0;
            animation: fadeIn 1.0s ease-in forwards;
            animation-delay: 0.5s; 
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div id="fishbowl" class="fishbowl">
            <div class="fishbowl__pool"></div>
            <div class="fishbowl__background"></div>
            <div class="fishbowl__bottom"></div>
            <div class="fishbowl__decoration">
                <div class="fishbowl__seaweed fishbowl__seaweed--1"></div>
                <div class="fishbowl__seaweed fishbowl__seaweed--2"></div>
                <div class="fishbowl__seaweed fishbowl__seaweed--3"></div>
            </div>
            <div class="fishbowl__water">
                <div id="fish" class="fishbowl__fish">
                    <div class="fishbowl__fish-tail"></div>
                </div>
                <div class="fishbowl__water-color"></div>
            </div>
            <div class="fishbowl__top"></div>
            <div id="tap" class="fishbowl__tap">
                <div class="fishbowl__tap-base"></div>
                <div class="fishbowl__tap-handle"></div>
                <div class="fishbowl__tap-stream"></div>
                <div class="fishbowl__tap-end"></div>
                <div class="fishbowl__tap-head"></div>
                <div class="fishbowl__tap-text">Click to refill</div>
            </div>
        </div>
        <div class="fade-in">
            <div class="welcome-title">Welcome to STREAM</div>
            <div class="welcome-desc">
                Smart Technology for Real-Time Environmental Aquatic Monitoring<br>
                <span style="font-size:1rem;color:#2196f3;">Empowering aquaculture with real-time insights.</span>
            </div>
            <button class="enter-btn" id="enter-btn">Enter Dashboard</button>
        </div>
    </div>
    <script>
        document.getElementById('enter-btn').addEventListener('click', function() {
            window.location.href = 'dashboard';
        });

        // Fishbowl animation logic
        let fill = 90;
        let intervalId = null;
        const fishbowl = document.getElementById('fishbowl');
        const fish = document.getElementById('fish');
        const tap = document.getElementById('tap');

        const emptyingFn = () => setInterval(() => {
            fill = fill - 1;
            fishbowl.style.setProperty('--filling', fill);
            if (fill <= 0) {
                clearInterval(intervalId);
            } else if (fill < 20) {
                fish.classList.add('fishbowl__fish--dead');
            } else if (fill < 50) {
                fish.classList.add('fishbowl__fish--dying');
            } else {
                fish.classList.remove('fishbowl__fish--dying');
                fish.classList.remove('fishbowl__fish--dead');
            }
        }, 200);

        intervalId = emptyingFn();

        tap.addEventListener('click', () => {
            tap.classList.add('fishbowl__tap--active');
            setTimeout(() => tap.classList.remove('fishbowl__tap--active'), 500);
            if (fill <= 0) {
                intervalId = emptyingFn();
                fish.classList.add('fishbowl__fish--floating');
            }
            fill = Math.min(fill + 20, 90);
            fishbowl.style.setProperty('--filling', fill);
        });
    </script>
</body>
</html>
