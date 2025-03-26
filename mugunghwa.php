<?php
	include_once('../../common.php');
	$g5['title'] = "무궁화꽃이 피었습니다!";
	include_once(G5_PATH.'/_head.php');
 
    if (!defined('_GNUBOARD_')) exit; // 그누보드 관련 필수 파일 포함



session_start();

// 게임 상태 초기화
if (!isset($_SESSION['game_state'])) {
    $_SESSION['game_state'] = [
        'player_position' => 0,
        'is_alive' => true,
        'distance_to_finish' => 100,
        'doll_watching' => false
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>무궁화꽃이 피었습니다</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
       .game-container {
            width: 90vw;
            max-width: 600px;
            height: 70vh;
            max-height: 400px;
            border: 2px solid #000;
            position: relative;
            margin: 10px auto;
            background: url('./background.jpg') no-repeat;
            background-size: cover;                      
        }
        .doll {
            width: 25%;
            max-width: 150px;
            height: 75%;
            max-height: 300px;
            background: url('./doll.jpg') no-repeat;
            background-size: contain;
            position: absolute;
            right: -33%;
            top: 12.5%;
            transition: transform 0.3s ease, background-image 0.3s ease;
            background-color: transparent;
        }

        @media (max-width: 480px) {
            .game-controls {
                flex-direction: column;
                align-items: center;
            }
            
            .difficulty-select, .player-select, .start-button {
                width: 80%;
            }

            .doll {
                left: 80%;  /* 모바일에서 인형 위치 조정 */
                top: 0%;  /* 모바일에서 인형 위치 조정 */
            }
        }
   
        
        .player {
            width: 13%;
            max-width: 80px;
            height: 40%;
            max-height: 160px;
            background: url('https://cdn.pixabay.com/photo/2016/03/31/19/58/avatar-1295429_640.png') no-repeat;
            background-size: contain;
            position: absolute;
            left: 0;
            transition: left 0.1s linear;
        }
        
        .game-status {
            text-align: center;
            font-size: clamp(16px, 4vw, 24px);
            margin: 10px;
        }


        .timer {
            text-align: center;
            font-size: clamp(14px, 3.5vw, 20px);
            margin: 5px;
        }
        .controls {
            text-align: center;
            margin: 10px;
            padding: 0 10px;
        }
    
        .difficulty-select, .player-select {
            padding: 8px;
            font-size: clamp(14px, 3.5vw, 16px);
            width: 100%;
            max-width: 150px;
        }
       

        .game-controls {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .start-button {
            padding: 8px 15px;
            font-size: clamp(14px, 3.5vw, 16px);
            width: 100%;
            max-width: 150px;
        }

        @media (max-width: 480px) {
            .game-controls {
                flex-direction: column;
                align-items: center;
            }
            
            .difficulty-select, .player-select, .start-button {
                width: 80%;
            }
        }

        .start-button:hover {
        background-color: #45a049;
        }


    </style>
</head>
<body>
    <div class="game-status" id="status">무궁화꽃이 피었습니다</div>
    <div class="timer" id="timer" style="text-align: center; font-size: 20px; margin: 10px;">Time: 60</div>
    <div class="game-container">
        <div class="doll" id="doll"></div>            
    </div>

    <div class="controls">
    <div class="game-controls">
        <select id="difficulty" class="difficulty-select">
            <option value="easy">Easy</option>
            <option value="medium">Medium</option>
            <option value="hard">Hard</option>
        </select>
        <select id="playerCount" class="player-select">
            <option value="1">1 Player</option>
            <option value="2">2 Players</option>
            <option value="3">3 Players</option>
        </select>
        <button onclick="startGame()" class="start-button">Start Game</button>
    </div>
        <div style="margin-top: 20px; padding: 15px; background-color: #f5f5f5; border-radius: 8px; display: inline-block; text-align: left;">
        <div style="font-weight: bold; margin-bottom: 10px; color: #333; font-size: 18px;">Desktop Controls</div>
        <div style="display: grid; gap: 8px; color: #444;">
        <div>Player 1: <span style="background-color: #e0e0e0; padding: 2px 8px; border-radius: 4px;">→</span> (Right Arrow)</div>
        <div>Player 2: <span style="background-color: #e0e0e0; padding: 2px 8px; border-radius: 4px;">D</span> key</div>
        <div>Player 3: <span style="background-color: #e0e0e0; padding: 2px 8px; border-radius: 4px;">L</span> key</div>
        <div style="font-weight: bold; margin-bottom: 1px; color: #333; font-size: 18px;">Mobile Controls</div>
        <div>Player 1: touch or drag</div>
    </div>
</div>
    </div>
    <!-- Add audio elements -->
    <audio id="turnSound" src="./turnsound.mp3"></audio>
    <audio id="eliminateSound" src="./eliminatesound.mp3"></audio>
    <audio id="winSound" src="./winsound.mp3"></audio>
    <audio id="mainsound" src="./mainsound.mp3"></audio>

    <script>
    let isGameOver = false;
    let isDollWatching = false;
    let players = [];
    let currentDifficulty = 'medium';
    let turnInterval;
    let gameTimer;
    let timeLeft = 60;
    let touchStartX = 0;
    let isTouching = false;
    

    function updateTimer() {
    timeLeft--;
    document.getElementById('timer').textContent = `Time: ${timeLeft}`;
    
    if (timeLeft <= 0) {
        isGameOver = true;
        document.getElementById('status').textContent = '시간 초과! 게임 오버!';
        clearInterval(turnInterval);
        clearInterval(gameTimer);
        playSound('eliminateSound'); // Optional: play sound when time's up
    }
    }

    // Add audio loading check
    window.onload = function() {
        const audioElements = document.querySelectorAll('audio');
        audioElements.forEach(audio => {
            audio.load();
        });
    }
    
    class Player {
        constructor(id) {
            this.id = id;
            this.position = 0;
            this.isMoving = false;
            this.isAlive = true;
            this.element = createPlayerElement(id);
        }
    }

    function createPlayerElement(id) {
    const player = document.createElement('div');
    player.className = 'player';
    player.id = `player${id}`;
    player.style.top = `${120 + (id * 90)}px`; // Adjusted spacing between players
    document.querySelector('.game-container').appendChild(player);
    return player;
}

    function startGame() {
    // Reset game state
    isGameOver = false;
    timeLeft = 60; // Reset timer
    document.getElementById('timer').textContent = `Time: ${timeLeft}`; // Reset timer display
    const playerCount = parseInt(document.getElementById('playerCount').value);
    currentDifficulty = document.getElementById('difficulty').value;
    
    // Clear existing players
    players.forEach(p => p.element.remove());
    players = [];
    
    // Create new players
    for(let i = 0; i < playerCount; i++) {
        players.push(new Player(i));
    }
    
    // Start doll animation with selected difficulty
    if(turnInterval) clearInterval(turnInterval);
    if(gameTimer) clearInterval(gameTimer);
    
    startDollAnimation();
    gameTimer = setInterval(updateTimer, 1000); // Start the timer
    }
    
    /*
    function playSound(soundId) {
        const sound = document.getElementById(soundId);
        sound.currentTime = 0;
        sound.play().catch(error => {
            console.log(`Error playing sound: ${error}`);
        });
    }
    */
    function playSound(soundId) {
    const sound = document.getElementById(soundId);
    sound.currentTime = 0;
    
    // Set playback speed based on difficulty for mainsound
    if (soundId === 'mainsound') {
        if (currentDifficulty === 'medium') {
            sound.playbackRate = 1.75;
        } else if (currentDifficulty === 'hard') {
            sound.playbackRate = 2.25;
        } else {
            sound.playbackRate = 1.25;
        }
    } else {
        sound.playbackRate = 1.25;  // Normal speed for other sounds
    }
    
    sound.play().catch(error => {
        console.log(`Error playing sound: ${error}`);
    });
    }


    function startDollAnimation() {
    const speeds = {
        easy: 2500,
        medium: 1500,
        hard: 1000
    };
    
    turnInterval = setInterval(() => {
        if (isGameOver) return;
        
        if (!isDollWatching) {
            playSound('turnSound');
            setTimeout(() => {
                isDollWatching = true;
                const doll = document.getElementById('doll');
                const status = document.getElementById('status');
                
                doll.style.transform = 'scaleX(-1)';
                //doll.style.background = 'url(./dollfront.jpg) no-repeat';
                //doll.style.backgroundSize = 'contain';
                doll.style.background = 'url(./dollfront.jpg) no-repeat center/contain';
                status.textContent = '무궁화꽃이 피었습니다!';
                
                players.forEach(player => {
                    if (player.isMoving && player.isAlive) {
                        eliminatePlayer(player);
                    }
                });
            }, 200);
        } else {
            isDollWatching = false;
            const doll = document.getElementById('doll');
            const status = document.getElementById('status');
            
            playSound('mainsound');
            doll.style.transform = 'scaleX(1)';
            //doll.style.background = 'url(./doll.jpg) no-repeat';
            //doll.style.backgroundSize = 'contain';
            doll.style.background = 'url(./doll.jpg) no-repeat center/contain';
            status.textContent = '무궁화...꽃이...';
        }
    }, speeds[currentDifficulty]);
}

    function eliminatePlayer(player) {
        player.isAlive = false;
        player.element.style.backgroundColor = 'red';
        playSound('eliminateSound');
        checkGameEnd();
    }

    function checkGameEnd() {
    const alivePlayers = players.filter(p => p.isAlive);
    if (alivePlayers.length === 0) {
        isGameOver = true;
        document.getElementById('status').textContent = '게임 오버!';
        clearInterval(gameTimer); // Clear the timer when game ends
    }
    }

    function checkWin(player) {
        const gameContainer = document.querySelector('.game-container');
        const winPosition = window.innerWidth <= 480 ? 
            gameContainer.offsetWidth * 0.8 : // 모바일에서는 컨테이너 너비의 80%
            600; // 데스크톱에서는 기존 600px

        if (player.position >= winPosition && player.isAlive) {
            document.getElementById('status').textContent = `Player ${player.id + 1} Wins!`;
            playSound('winSound');
            isGameOver = true;
            clearInterval(gameTimer);
        }
    }

   // 게임 컨테이너에 터치 이벤트 리스너 추가
   document.querySelector('.game-container').addEventListener('touchstart', (e) => {
        if (isGameOver) return;
        e.preventDefault(); // 기본 터치 동작 방지
        touchStartX = e.touches[0].clientX;
        isTouching = true;
        if (players[0]) {
            handlePlayerMove(players[0]);
        }
    });

    document.querySelector('.game-container').addEventListener('touchmove', (e) => {
        if (isGameOver || !isTouching) return;
        e.preventDefault();
        const touchX = e.touches[0].clientX;
        const deltaX = touchX - touchStartX;
        
        if (deltaX > 0 && players[0]) { // 오른쪽으로 드래그할 때만 이동
            handlePlayerMove(players[0]);
        }
        touchStartX = touchX;
    });


    document.querySelector('.game-container').addEventListener('touchend', (e) => {
        e.preventDefault();
        isTouching = false;
        if (players[0]) {
            players[0].isMoving = false;
        }
    });


    document.querySelector('.game-container').addEventListener('touchcancel', (e) => {
        e.preventDefault();
        isTouching = false;
        if (players[0]) {
            players[0].isMoving = false;
        }
    });

    function handlePlayerMove(player) {
        if (!player || !player.isAlive) return;
        
        player.isMoving = true;
        // 모바일 환경에서는 더 천천히 이동 (mobile시 drag시 너무 빨리 이동하므로 강제로 속도 저하 0.8 배속)
        const moveSpeed = window.innerWidth <= 480 ? 0.8 : 5;
        player.position += moveSpeed;
        player.element.style.left = player.position + 'px';
        
        if (isDollWatching) {
            eliminatePlayer(player);
        }
        
        checkWin(player);
    }

    document.addEventListener('keydown', (e) => {
        if (isGameOver) return;
        
        if (e.key === 'ArrowRight') {  // isTouching 조건 제거
            handlePlayerMove(players[0]);
        } else if (e.key === 'd') {
            handlePlayerMove(players[1]);
        } else if (e.key === 'l') {
            handlePlayerMove(players[2]);
        }
    });

    document.addEventListener('keyup', (e) => {
        if (e.key === 'ArrowRight') {
            if (players[0]) players[0].isMoving = false;
        } else if (e.key === 'd') {
            if (players[1]) players[1].isMoving = false;
        } else if (e.key === 'l') {
            if (players[2]) players[2].isMoving = false;
        }
    });

</script>
</body>
</html>
<?php
include_once(G5_PATH.'/tail.php');
?>