<?php
/**
 * YOUR_NAME - 页脚
 */
?>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>🐱 <?= SITE_NAME ?></h3>
                <p>YOUR_NAME 的小窝</p>
                <div class="social-links">
                    <a href="https://github.com" target="_blank" title="GitHub">🐙</a>
                    <a href="https://twitter.com" target="_blank" title="Twitter">🐦</a>
                    <a href="https://bilibili.com" target="_blank" title="Bilibili">📺</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?= date('Y') ?> <?= SITE_NAME ?> | Powered by YOUR_NAME 🐱</p>
            <p class="visitor-count">访客数: <span id="visitorCount">—</span></p>
        </div>
    </div>
</footer>

<!-- 设置面板 -->
<div class="settings-panel" id="settingsPanel">
    <button class="settings-close" onclick="toggleSettings()">✕</button>
    <h3>⚙️ 设置</h3>
    <div class="setting-item">
        <label>🎵 音乐播放器</label>
        <label class="switch">
            <input type="checkbox" id="musicToggle" onchange="toggleMusicVisibility()">
            <span class="slider"></span>
        </label>
    </div>
    <div class="setting-item">
        <label>✨ 粒子背景</label>
        <label class="switch">
            <input type="checkbox" id="particlesToggle" checked onchange="toggleParticles()">
            <span class="slider"></span>
        </label>
    </div>
</div>

<!-- 音乐播放器 -->
<div class="music-player" id="musicPlayer">
    <div class="music-disc" id="musicDisc">🎵</div>
    <div class="music-info">
        <div class="music-title">赛博朋克BGM</div>
        <div class="music-artist">—</div>
    </div>
    <div class="music-controls">
        <button class="music-btn" onclick="prevTrack()">⏮</button>
        <button class="music-btn" id="musicPlay" onclick="toggleMusic()">▶</button>
        <button class="music-btn" onclick="nextTrack()">⏭</button>
    </div>
</div>

<!-- 设置按钮 -->
<button class="settings-btn" id="settingsBtn" onclick="toggleSettings()">⚙️</button>

<script>
// 音乐播放器
let musicVisible = false;
let isPlaying = false;

function toggleMusicVisibility() {
    const player = document.getElementById('musicPlayer');
    musicVisible = !musicVisible;
    player.classList.toggle('visible', musicVisible);
    document.getElementById('musicToggle').checked = musicVisible;
}

function toggleMusic() {
    isPlaying = !isPlaying;
    document.getElementById('musicPlay').textContent = isPlaying ? '⏸' : '▶';
    document.getElementById('musicDisc').style.animationPlayState = isPlaying ? 'running' : 'paused';
}

function prevTrack() {}
function nextTrack() {}

// 粒子背景开关
function toggleParticles() {
    const canvas = document.getElementById('particles');
    if (canvas) {
        canvas.style.display = document.getElementById('particlesToggle').checked ? 'block' : 'none';
    }
}

// 设置面板
function toggleSettings() {
    const panel = document.getElementById('settingsPanel');
    panel.classList.toggle('open');
}

// 阅读进度条 + 回到顶部
window.addEventListener('scroll', function() {
    const winScroll = document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
    const bar = document.getElementById('readingProgress');
    if (bar) bar.style.width = scrolled + '%';
    
    const nav = document.getElementById('navbar');
    if (nav) {
        if (winScroll > 50) nav.classList.add('scrolled');
        else nav.classList.remove('scrolled');
    }
    
    const btn = document.getElementById('backToTop');
    if (btn) {
        if (winScroll > 300) btn.classList.add('visible');
        else btn.classList.remove('visible');
    }
});

// 访客计数
function updateVisitorCount() {
    fetch('api_visitor.php')
        .then(r => r.json())
        .then(data => {
            const el = document.getElementById('visitorCount');
            if (el) el.textContent = data.count || '—';
        })
        .catch(() => {
            let count = parseInt(localStorage.getItem('visitorCount')) || Math.floor(Math.random() * 200 + 50);
            count++;
            localStorage.setItem('visitorCount', count);
            const el = document.getElementById('visitorCount');
            if (el) el.textContent = count;
        });
}

// 控制台彩蛋
console.log('%c🐱 欢迎来到YOUR_NAME！', 'color: #6c9fff; font-size: 20px; font-weight: bold;');
console.log('%cYOUR_NAME 构建的博客 ✨', 'color: #a855f7; font-size: 14px;');

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    updateVisitorCount();
});
</script>
