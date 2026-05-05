// 🐱 fmy - Particle Background (优化版)
(function() {
    const canvas = document.getElementById('particles');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    let particles = [];
    let mouse = { x: null, y: null, active: false };
    let animId = null;
    let isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    function resize() {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = window.innerWidth * dpr;
        canvas.height = window.innerHeight * dpr;
        canvas.style.width = window.innerWidth + 'px';
        canvas.style.height = window.innerHeight + 'px';
        ctx.scale(dpr, dpr);
    }
    resize();
    window.addEventListener('resize', resize);
    
    document.addEventListener('mousemove', function(e) {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
        mouse.active = true;
    });
    document.addEventListener('mouseleave', function() {
        mouse.active = false;
    });
    
    // Touch support
    document.addEventListener('touchmove', function(e) {
        if (e.touches.length > 0) {
            mouse.x = e.touches[0].clientX;
            mouse.y = e.touches[0].clientY;
            mouse.active = true;
        }
    });
    
    class Particle {
        constructor() {
            this.reset();
        }
        
        reset() {
            this.x = Math.random() * window.innerWidth;
            this.y = Math.random() * window.innerHeight;
            this.baseSize = Math.random() * 1.5 + 0.5;
            this.size = this.baseSize;
            this.speedX = (Math.random() - 0.5) * 0.3;
            this.speedY = (Math.random() - 0.5) * 0.3;
            this.opacity = Math.random() * 0.4 + 0.1;
            this.pulseSpeed = Math.random() * 0.02 + 0.005;
            this.pulsePhase = Math.random() * Math.PI * 2;
            this.pulseAmp = Math.random() * 0.05;
            // Cyan, magenta, purple
            const colors = [
                {r:0, g:240, b:255},    // cyan
                {r:255, g:0, b:170},     // magenta
                {r:136, g:85, b:255}     // purple
            ];
            this.color = colors[Math.floor(Math.random() * colors.length)];
        }
        
        update(time) {
            // Pulse effect
            this.size = this.baseSize + Math.sin(time * this.pulseSpeed + this.pulsePhase) * this.pulseAmp;
            
            // Mouse interaction
            if (mouse.active) {
                const dx = this.x - mouse.x;
                const dy = this.y - mouse.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                const maxDist = 100;
                if (dist < maxDist) {
                    const force = (maxDist - dist) / maxDist;
                    this.x += (dx / dist) * force * 2;
                    this.y += (dy / dist) * force * 2;
                }
            }
            
            this.x += this.speedX;
            this.y += this.speedY;
            
            // Wrap around
            if (this.x < 0) this.x = window.innerWidth;
            if (this.x > window.innerWidth) this.x = 0;
            if (this.y < 0) this.y = window.innerHeight;
            if (this.y > window.innerHeight) this.y = 0;
        }
        
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, Math.max(0.1, this.size), 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, ${this.opacity})`;
            ctx.fill();
        }
    }
    
    // Adaptive particle count
    const area = window.innerWidth * window.innerHeight;
    const maxParticles = isReducedMotion ? 20 : Math.min(60, Math.floor(area / 20000));
    for (let i = 0; i < maxParticles; i++) {
        particles.push(new Particle());
    }
    
    function connectParticles() {
        const maxDist = isReducedMotion ? 80 : 100;
        for (let a = 0; a < particles.length; a++) {
            for (let b = a + 1; b < particles.length; b++) {
                const dx = particles[a].x - particles[b].x;
                const dy = particles[a].y - particles[b].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                
                if (dist < maxDist) {
                    const alpha = 0.06 * (1 - dist / maxDist);
                    ctx.beginPath();
                    ctx.strokeStyle = `rgba(0, 240, 255, ${alpha})`;
                    ctx.lineWidth = 0.5;
                    ctx.moveTo(particles[a].x, particles[a].y);
                    ctx.lineTo(particles[b].x, particles[b].y);
                    ctx.stroke();
                }
            }
        }
    }
    
    function animate(time) {
        ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
        
        particles.forEach(p => {
            p.update(time || 0);
            p.draw();
        });
        
        connectParticles();
        animId = requestAnimationFrame(animate);
    }
    
    animate(0);
})();
