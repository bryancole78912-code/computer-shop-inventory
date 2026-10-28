// ==========================================================
// Static Galaxy Cosmic Background
// Clean, Calm, Stationary Galaxy Stars & Nebulae
// ==========================================================

(function() {
    let canvas, ctx;

    function initGalaxy() {
        canvas = document.getElementById('galaxyStarsCanvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'galaxyStarsCanvas';
            document.body.prepend(canvas);
        }
        ctx = canvas.getContext('2d');

        renderStaticGalaxy();
        window.addEventListener('resize', renderStaticGalaxy);
    }

    function renderStaticGalaxy() {
        if (!canvas) return;
        const width = canvas.width = window.innerWidth;
        const height = canvas.height = window.innerHeight;

        // 1. Deep Space Dark Background Base
        ctx.fillStyle = '#030712';
        ctx.fillRect(0, 0, width, height);

        // 2. Cosmic Nebulae (Stellar Gas Clouds)
        const nebulae = [
            { x: width * 0.18, y: height * 0.22, r: width * 0.45, c0: 'rgba(56, 189, 248, 0.14)', c1: 'rgba(99, 102, 241, 0.05)' },
            { x: width * 0.82, y: height * 0.28, r: width * 0.50, c0: 'rgba(168, 85, 247, 0.14)', c1: 'rgba(236, 72, 153, 0.04)' },
            { x: width * 0.50, y: height * 0.65, r: width * 0.55, c0: 'rgba(14, 165, 233, 0.10)', c1: 'rgba(147, 51, 234, 0.03)' },
            { x: width * 0.20, y: height * 0.82, r: width * 0.38, c0: 'rgba(236, 72, 153, 0.07)', c1: 'transparent' },
            { x: width * 0.85, y: height * 0.85, r: width * 0.40, c0: 'rgba(20, 184, 166, 0.08)', c1: 'transparent' }
        ];

        nebulae.forEach(n => {
            const grad = ctx.createRadialGradient(n.x, n.y, 0, n.x, n.y, n.r);
            grad.addColorStop(0, n.c0);
            grad.addColorStop(0.5, n.c1);
            grad.addColorStop(1, 'transparent');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, width, height);
        });

        // 3. Static Galaxy Stars (Stationary & Crisp)
        const STAR_COUNT = 550;
        const colors = ['#ffffff', '#e0f2fe', '#bae6fd', '#ddd6fe', '#fef08a', '#fbcfe8'];

        // Deterministic pseudo-random seed to keep the stars completely stationary
        let seed = 12345;
        function rand() {
            seed = (seed * 9301 + 49297) % 233280;
            return seed / 233280;
        }

        for (let i = 0; i < STAR_COUNT; i++) {
            const x = rand() * width;
            const y = rand() * height;
            const size = rand() * 1.5 + 0.4;
            const color = colors[Math.floor(rand() * colors.length)];
            const alpha = rand() * 0.65 + 0.25;

            ctx.save();
            ctx.globalAlpha = alpha;
            ctx.fillStyle = color;
            ctx.beginPath();
            ctx.arc(x, y, size, 0, Math.PI * 2);
            ctx.fill();

            // Subtle soft celestial corona for brighter stars
            if (size > 1.3) {
                ctx.globalAlpha = alpha * 0.35;
                ctx.beginPath();
                ctx.arc(x, y, size * 2.4, 0, Math.PI * 2);
                ctx.fill();
            }
            ctx.restore();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGalaxy);
    } else {
        initGalaxy();
    }
})();
