<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WASDA.AI | Attention Explorer</title>
    <meta name="description" content="Interactive visualization of Transformer Self-Attention.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0d1117">
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect x=%2210%22 y=%2210%22 width=%2280%22 height=%2280%22 rx=%2220%22 fill=%22%23238636%22/><path d=%22M30 50 L45 65 L70 35%22 stroke=%22white%22 stroke-width=%2210%22 fill=%22none%22 stroke-linecap=%22round%22/></svg>">

    <style>
        /* --- DESIGN SYSTEM --- */
        :root {
            --bg: #0d1117;
            --panel-bg: #161b22;
            --text-main: #e6edf3;
            --text-muted: #8b949e;
            --border: #30363d;
            --brand: #238636; 
            --accent: #58a6ff;
            
            /* Heads */
            --c-head0: #22d3ee; 
            --c-head1: #e879f9; 
            --c-head2: #fbbf24; 
        }

        * { box-sizing: border-box; }
        
        html, body {
            margin: 0; padding: 0;
            width: 100%; height: 100dvh;
            background-color: var(--bg);
            font-family: 'SF Mono', 'Segoe UI Mono', 'Roboto Mono', monospace;
            color: var(--text-main);
            overflow: hidden; 
            -webkit-user-select: none; user-select: none;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        #app {
            display: flex; flex-direction: column;
            height: 100dvh; max-width: 800px; margin: 0 auto;
        }

        /* --- HEADER --- */
        header {
            flex: 0 0 auto;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            background: var(--bg); z-index: 10;
        }
        .brand-block { display: flex; flex-direction: column; }
        h1 {
            margin: 0; font-size: 16px; font-weight: 700;
            letter-spacing: -0.5px; color: var(--text-main);
        }
        .tag {
            font-size: 9px; color: var(--brand);
            border: 1px solid var(--brand); padding: 1px 5px;
            border-radius: 4px; align-self: flex-start;
            margin-top: 4px; font-weight: 600; letter-spacing: 0.5px;
        }
        .status-block {
            font-size: 10px; color: var(--text-muted);
            display: flex; align-items: center; gap: 8px;
        }
        .led {
            width: 8px; height: 8px; border-radius: 50%;
            background: #21262d; border: 1px solid #30363d;
            transition: 0.3s;
        }
        .led.on { background: var(--brand); box-shadow: 0 0 8px var(--brand); border-color: var(--brand); }

        /* --- VIZ --- */
        #viz-container {
            flex: 1 1 auto; position: relative;
            display: flex; flex-direction: column;
            justify-content: flex-end; overflow: hidden;
            padding-bottom: 16px; min-height: 0;
        }
        canvas {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            z-index: 1; pointer-events: none; 
        }
        #token-row {
            display: flex; justify-content: center; align-items: center;
            gap: 6px; padding: 6px 16px; z-index: 2;
            width: 100%; overflow-x: auto; scrollbar-width: none;
        }
        #token-row::-webkit-scrollbar { display: none; }

        .token {
            padding: 8px 12px;
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 8px; font-size: 14px; cursor: pointer;
            transition: all 0.1s cubic-bezier(0.2, 0.8, 0.2, 1); white-space: nowrap;
        }
        .token:active { transform: scale(0.92); }
        .token.active {
            background: #21262d; border-color: var(--text-main);
            color: #fff; box-shadow: 0 0 0 1px var(--text-main);
            z-index: 5;
        }

        /* --- CONTROLS --- */
        #bottom-panel {
            flex: 0 0 auto; 
            background: var(--panel-bg);
            border-top: 1px solid var(--border);
            padding: 14px 16px;
            display: flex; gap: 14px;
            padding-bottom: max(14px, env(safe-area-inset-bottom));
        }
        .panel-left { flex: 0 0 auto; display: flex; flex-direction: column; }
        .panel-right { flex: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 10px; }

        .label {
            font-size: 9px; color: var(--text-muted);
            margin-bottom: 6px; text-transform: uppercase;
            display: flex; justify-content: space-between; font-weight: 700;
        }

        #heatmap-grid {
            display: grid; gap: 1px;
            background: var(--bg); border: 1px solid var(--border);
        }
        .hm-cell {
            width: clamp(12px, 4vw, 22px); height: clamp(12px, 4vw, 22px);
            background-color: var(--bg); transition: background-color 0.1s;
        }
        .hm-cell.active-row { box-shadow: inset 0 0 0 1px rgba(255,255,255,0.6); z-index: 2; }

        .btn-row { display: flex; gap: 8px; width: 100%; }
        button {
            background: transparent; border: 1px solid var(--border);
            color: var(--text-muted); padding: 10px 0;
            font-family: inherit; font-size: 10px; font-weight: 700;
            border-radius: 6px; cursor: pointer; text-transform: uppercase;
            transition: 0.2s; flex: 1;
        }
        button:active { transform: scale(0.96); background: var(--border); }
        button.active {
            background: var(--border); color: var(--text-main);
            border-color: var(--text-muted);
        }

        /* SLIDER */
        input[type=range] {
            -webkit-appearance: none; width: 100%; background: transparent; padding: 6px 0;
        }
        input[type=range]:focus { outline: none; }
        input[type=range]::-webkit-slider-runnable-track {
            width: 100%; height: 4px; cursor: pointer;
            background: #30363d; border-radius: 2px;
        }
        input[type=range]::-webkit-slider-thumb {
            height: 20px; width: 20px; border-radius: 50%;
            background: var(--accent); cursor: pointer;
            -webkit-appearance: none; margin-top: -8px; 
            border: 3px solid var(--panel-bg);
            box-shadow: 0 2px 6px rgba(0,0,0,0.5);
        }

        .dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; margin-right: 4px; }
        .c0 { background-color: var(--c-head0); }
        .c1 { background-color: var(--c-head1); }
        .c2 { background-color: var(--c-head2); }

        @media (max-height: 660px) {
            header { padding: 8px 16px; }
            #bottom-panel { padding: 10px 16px; padding-bottom: max(10px, env(safe-area-inset-bottom)); }
            .token { padding: 6px 10px; font-size: 13px; }
            button { padding: 8px 0; }
        }
    </style>
</head>
<body>

<div id="app">
    <header>
        <div class="brand-block">
            <h1>WASDA.AI</h1>
            <span class="tag">INTERACTIVE DEMO</span>
        </div>
        <div class="status-block">
            <div class="led" id="audio-led"></div>
            <span id="status-text">SILENT</span>
        </div>
    </header>

    <div id="viz-container">
        <canvas id="attention-canvas"></canvas>
        <div id="token-row"></div>
    </div>

    <div id="bottom-panel">
        <div class="panel-left">
            <div class="label">Softmax</div>
            <div id="heatmap-container">
                <div id="heatmap-grid"></div>
            </div>
        </div>

        <div class="panel-right">
            <div style="width:100%">
                <div class="label">
                    <span>Temperature</span>
                    <span id="temp-val" style="color: var(--accent)">1.00</span>
                </div>
                <input type="range" id="temp-slider" min="0.1" max="2.0" step="0.1" value="1.0">
            </div>

            <div>
                <div class="label" style="text-align:right">Attention Head</div>
                <div class="btn-row" id="head-controls">
                    <button data-head="all" class="active">All</button>
                    <button data-head="0"><span class="dot c0"></span>1</button>
                    <button data-head="1"><span class="dot c1"></span>2</button>
                    <button data-head="2"><span class="dot c2"></span>3</button>
                </div>
            </div>

            <div class="btn-row">
                 <button id="btn-random">Next Sample</button>
                 <button id="btn-sound">Audio Engine</button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * WASDA.AI AUDIO ENGINE (Distinct Heads Edition)
 */
const AudioEngine = {
    ctx: null,
    enabled: false,
    
    // Scale (Eb Major Pentatonic)
    scale: [155.56, 185.00, 207.65, 233.08, 277.18, 311.13, 369.99, 415.30],

    init() { 
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        this.ctx = new AudioContext(); 
    },

    toggle() {
        if (!this.ctx) this.init();
        if (this.ctx.state === 'suspended') this.ctx.resume();
        this.enabled = !this.enabled;
        return this.enabled;
    },

    play(index, temp, head, totalTokens) {
        if (!this.enabled || !this.ctx) return;
        const t = this.ctx.currentTime;
        
        // PITCH & PAN
        const stepIdx = index % this.scale.length;
        let freq = this.scale[stepIdx];
        const panVal = ((index / Math.max(1, totalTokens - 1)) * 2) - 1;

        // THERMODYNAMICS
        const chaos = Math.max(0, Math.min(1, (temp - 0.5) / 1.5));
        const decay = 0.1 + (chaos * 2.5); 

        // INSTRUMENT SELECTION LOGIC
        let type = 'sine';
        let fmRatio = 0; // 0 means disabled
        let modulationIdx = 0;
        let filterType = 'lowpass';
        let detune = 0;

        if (head === 0) {
            // HEAD 1 (Cyan): The "Pulse" (Clean, Hollow)
            // Represents local structure.
            type = 'triangle'; 
            // Filter opens/closes with temp
            
        } else if (head === 1) {
            // HEAD 2 (Magenta): The "Bell" (Glassy)
            // Represents global context.
            type = 'sine';
            fmRatio = 1.5; // Glassy ratio
            modulationIdx = 200 + (temp * 200);

        } else if (head === 2) {
            // HEAD 3 (Amber): The "Buzz" (Electric)
            // Represents semantic meaning.
            type = 'sawtooth';
            filterType = 'bandpass'; // Nasal sound
            detune = 10 + (chaos * 50); // Slightly out of tune

        } else {
            // ALL: Play a stacked sound
            // We'll handle this by recursively calling play for a chord effect
            // But to keep it simple and performant, we'll do a rich Square wave
            type = 'square';
            detune = chaos * 20;
        }

        // --- SYNTHESIS GRAPH ---
        const osc = this.ctx.createOscillator();
        osc.type = type;
        osc.frequency.value = freq;
        osc.detune.value = detune;

        const gain = this.ctx.createGain();
        const panner = this.ctx.createStereoPanner();
        const filter = this.ctx.createBiquadFilter();
        
        filter.type = filterType;
        // Temperature controls brightness
        filter.frequency.value = 300 + (temp * 1200); 
        filter.frequency.exponentialRampToValueAtTime(100, t + decay);

        panner.pan.value = panVal;

        // Envelope
        gain.gain.setValueAtTime(0, t);
        gain.gain.linearRampToValueAtTime(0.15, t + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, t + decay);

        // WIRING
        let source = osc;

        // Apply FM if needed (Head 2)
        if (fmRatio > 0) {
            const mod = this.ctx.createOscillator();
            const modGain = this.ctx.createGain();
            mod.type = 'sine';
            mod.frequency.value = freq * fmRatio;
            modGain.gain.value = modulationIdx;
            
            mod.connect(modGain);
            modGain.connect(osc.frequency);
            mod.start(t);
            mod.stop(t + decay);
        }

        // Wiring Chain
        source.connect(filter);
        filter.connect(gain);
        gain.connect(panner);
        panner.connect(this.ctx.destination);

        // Start
        osc.start(t);
        osc.stop(t + decay + 0.1);

        // CHORD LOGIC FOR "ALL" HEADS
        // If "ALL" is selected, play a secondary note (Perfect 5th) to make it sound huge
        if (head === 'all') {
             this.playLayer(freq * 1.5, t, decay, panVal, 'sine', temp);
        }
    },

    // Helper for the "ALL" chord layer
    playLayer(freq, startTime, duration, pan, type, temp) {
        const osc = this.ctx.createOscillator();
        const g = this.ctx.createGain();
        const p = this.ctx.createStereoPanner();
        
        osc.type = type;
        osc.frequency.value = freq;
        p.pan.value = pan;
        
        g.gain.setValueAtTime(0, startTime);
        g.gain.linearRampToValueAtTime(0.1, startTime + 0.05);
        g.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
        
        osc.connect(g);
        g.connect(p);
        p.connect(this.ctx.destination);
        osc.start(startTime);
        osc.stop(startTime + duration);
    }
};

/* --- APP LOGIC --- */
const WASDA = {
    colors: ['#22d3ee', '#e879f9', '#fbbf24'],
    sentences: [
        "The cat sat on the mat.",
        "Attention is all you need.",
        "Deep learning models adapt.",
        "Thermodynamics alters logic.",
        "Probability creates reality.",
        "Visualizing data represents joy.",
        "Inputs flow into layers."
    ],

    state: {
        sentenceIdx: 0, tokens: [],
        numHeads: 3, temperature: 1.0,
        rawScores: [], weights: [],   
        activeHead: 'all', activeQueryIdx: 0,
        displayWeights: [] 
    },

    init() {
        this.canvas = document.getElementById('attention-canvas');
        this.ctx = this.canvas.getContext('2d');
        this.tokenRow = document.getElementById('token-row');
        this.heatmapGrid = document.getElementById('heatmap-grid');
        this.headControls = document.getElementById('head-controls');
        this.tempSlider = document.getElementById('temp-slider');
        this.tempLabel = document.getElementById('temp-val');
        this.soundBtn = document.getElementById('btn-sound');
        this.led = document.getElementById('audio-led');
        this.statusText = document.getElementById('status-text');
        
        window.addEventListener('resize', () => this.handleResize());
        this.handleResize(); 

        this.tempSlider.addEventListener('input', (e) => {
            const val = parseFloat(e.target.value);
            this.state.temperature = val;
            this.tempLabel.textContent = val.toFixed(2);
            this.recalcWeights(); 
            this.updateHeatmapColors();
        });

        document.getElementById('btn-random').addEventListener('click', () => {
            this.randomSentence();
        });
        
        this.soundBtn.addEventListener('click', () => {
            const isOn = AudioEngine.toggle();
            this.soundBtn.textContent = isOn ? "Audio: ON" : "Audio Engine";
            this.statusText.textContent = isOn ? "ONLINE" : "SILENT";
            this.led.classList.toggle('on', isOn);
            this.soundBtn.classList.toggle('active', isOn);
        });

        this.headControls.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const h = e.currentTarget.dataset.head;
                this.setHead(h === 'all' ? 'all' : parseInt(h));
                this.headControls.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                e.currentTarget.classList.add('active');
            });
        });

        this.randomSentence();
        this.animate();
    },

    handleResize() {
        const container = document.getElementById('viz-container');
        const dpr = window.devicePixelRatio || 1;
        const rect = container.getBoundingClientRect();
        this.canvas.width = rect.width * dpr;
        this.canvas.height = rect.height * dpr;
        this.ctx.scale(dpr, dpr);
    },

    randomSentence() {
        let newIdx;
        do { newIdx = Math.floor(Math.random() * this.sentences.length); } 
        while (newIdx === this.state.sentenceIdx && this.sentences.length > 1);
        
        this.state.sentenceIdx = newIdx;
        const text = this.sentences[newIdx];
        this.state.tokens = text.split(' ');
        this.state.activeQueryIdx = 0;
        
        this.generateRawScores();
        this.recalcWeights();
        this.buildDomElements();
        this.initDisplayWeights();
    },

    generateRawScores() {
        const N = this.state.tokens.length;
        this.state.rawScores = [];
        for (let h = 0; h < this.state.numHeads; h++) {
            const matrix = [];
            for (let i = 0; i < N; i++) {
                const row = [];
                for (let j = 0; j < N; j++) {
                    let score = 0;
                    if (h === 0) { 
                        const dist = Math.abs(i - j);
                        score = (dist === 1) ? 3.0 : (dist === 0 ? 1.0 : -dist);
                    } else if (h === 1) { 
                        if (j === 0) score += 2.0;
                        if ((i + j) % 3 === 0) score += 1.5; else score -= 0.5;
                    } else { 
                        if (i === j) score = 2.0; else if (j > i) score = 0.5; else score = -1.0;
                    }
                    score += (Math.random() * 0.8);
                    row.push(score);
                }
                matrix.push(row);
            }
            this.state.rawScores.push(matrix);
        }
    },

    recalcWeights() {
        const T = this.state.temperature;
        const N = this.state.tokens.length;
        this.state.weights = [];
        for (let h = 0; h < this.state.numHeads; h++) {
            const matrix = [];
            for (let i = 0; i < N; i++) {
                const rawRow = this.state.rawScores[h][i];
                const scaledRow = rawRow.map(x => x / T);
                const max = Math.max(...scaledRow); 
                const exps = scaledRow.map(x => Math.exp(x - max));
                const sum = exps.reduce((a, b) => a + b, 0);
                matrix.push(exps.map(x => x / sum));
            }
            this.state.weights.push(matrix);
        }
    },

    initDisplayWeights() {
        const N = this.state.tokens.length;
        this.state.displayWeights = [];
        for(let h=0; h<this.state.numHeads; h++){
            const mat = [];
            for(let i=0; i<N; i++) mat.push(new Array(N).fill(0));
            this.state.displayWeights.push(mat);
        }
    },

    setQuery(index) {
        this.state.activeQueryIdx = index;
        this.updateTokenStyles();
        this.updateHeatmapHighlights();
        
        AudioEngine.play(
            index, 
            this.state.temperature, 
            this.state.activeHead, 
            this.state.tokens.length
        );
    },

    setHead(val) {
        this.state.activeHead = val;
        this.updateHeatmapColors();
    },

    buildDomElements() {
        this.tokenRow.innerHTML = '';
        this.state.tokens.forEach((text, idx) => {
            const span = document.createElement('span');
            span.className = 'token';
            if (idx === 0) span.classList.add('active');
            span.textContent = text;
            const trigger = (e) => { 
                if (e.cancelable) e.preventDefault(); 
                this.setQuery(idx); 
            };
            span.addEventListener('mousedown', trigger);
            span.addEventListener('touchstart', trigger, {passive: false});
            this.tokenRow.appendChild(span);
        });

        const N = this.state.tokens.length;
        this.heatmapGrid.innerHTML = '';
        this.heatmapGrid.style.gridTemplateColumns = `repeat(${N}, 1fr)`;
        for(let i=0; i<N; i++) {
            for(let j=0; j<N; j++) {
                const cell = document.createElement('div');
                cell.className = 'hm-cell';
                cell.dataset.r = i; cell.dataset.c = j;
                this.heatmapGrid.appendChild(cell);
            }
        }
        this.updateHeatmapColors();
        this.updateHeatmapHighlights();
    },

    updateTokenStyles() {
        const tokens = this.tokenRow.children;
        for(let i=0; i<tokens.length; i++) {
            if(i === this.state.activeQueryIdx) tokens[i].classList.add('active');
            else tokens[i].classList.remove('active');
        }
    },

    updateHeatmapColors() {
        const cells = this.heatmapGrid.children;
        const N = this.state.tokens.length;
        for(let i=0; i<N; i++) {
            for(let j=0; j<N; j++) {
                const cell = cells[i * N + j];
                let val = 0;
                let colorBase = '#ffffff'; 
                if (this.state.activeHead === 'all') {
                    let sum = 0;
                    for(let h=0; h<this.state.numHeads; h++) sum += this.state.weights[h][i][j];
                    val = sum / this.state.numHeads;
                } else {
                    val = this.state.weights[this.state.activeHead][i][j];
                    colorBase = this.colors[this.state.activeHead];
                }
                const alpha = Math.min(1, val * 2.5); 
                cell.style.backgroundColor = colorBase;
                cell.style.opacity = 0.1 + (alpha * 0.9);
            }
        }
    },

    updateHeatmapHighlights() {
        const cells = this.heatmapGrid.children;
        Array.from(cells).forEach(cell => {
            const r = parseInt(cell.dataset.r);
            if (r === this.state.activeQueryIdx) cell.classList.add('active-row');
            else cell.classList.remove('active-row');
        });
    },

    animate() {
        this.lerpWeights();
        this.renderArcs();
        requestAnimationFrame(() => this.animate());
    },

    lerpWeights() {
        const N = this.state.tokens.length;
        const lerpFactor = 0.2; 
        for(let h=0; h<this.state.numHeads; h++) {
            const isActive = (this.state.activeHead === 'all' || this.state.activeHead === h);
            for(let i=0; i<N; i++) {
                for(let j=0; j<N; j++) {
                    let target = 0;
                    if (isActive && i === this.state.activeQueryIdx) target = this.state.weights[h][i][j];
                    const current = this.state.displayWeights[h][i][j];
                    this.state.displayWeights[h][i][j] += (target - current) * lerpFactor;
                }
            }
        }
    },

    renderArcs() {
        const { width, height } = this.canvas;
        this.ctx.clearRect(0, 0, width, height);
        const tokenEls = this.tokenRow.children;
        if (tokenEls.length === 0) return;
        const positions = [];
        const canvasRect = this.canvas.getBoundingClientRect();
        for(let el of tokenEls) {
            const r = el.getBoundingClientRect();
            positions.push({
                x: r.left + r.width/2 - canvasRect.left,
                y: r.top - canvasRect.top
            });
        }
        const N = this.state.tokens.length;
        const queryIdx = this.state.activeQueryIdx;
        const origin = positions[queryIdx];
        if(!origin) return;

        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';

        for(let h=0; h<this.state.numHeads; h++) {
            const color = this.colors[h];
            const rowWeights = this.state.displayWeights[h][queryIdx];
            for(let targetIdx=0; targetIdx<N; targetIdx++) {
                const weight = rowWeights[targetIdx];
                if (weight < 0.005) continue;
                const target = positions[targetIdx];
                const opacity = Math.min(0.9, Math.max(0.1, weight * 2.2));
                const lineWidth = Math.max(1.5, weight * 8); 
                this.ctx.beginPath();
                this.ctx.strokeStyle = color;
                this.ctx.lineWidth = lineWidth;
                this.ctx.globalAlpha = opacity;
                const midX = (origin.x + target.x) / 2;
                const dist = Math.abs(origin.x - target.x);
                const arcHeight = Math.min(height * 0.65, dist * 0.6 + 20); 
                const cpY = origin.y - arcHeight;
                this.ctx.moveTo(origin.x, origin.y);
                this.ctx.quadraticCurveTo(midX, cpY, target.x, target.y);
                this.ctx.stroke();
            }
        }
        this.ctx.globalAlpha = 1.0; 
    }
};

document.addEventListener('DOMContentLoaded', () => { WASDA.init(); });
</script>
</body>
</html>
