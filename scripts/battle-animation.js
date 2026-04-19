// Professional Battle Animation System - Fixed for left-facing planes
class BattleAnimation {
    constructor(plane1Data, plane2Data, winner, advantages) {
        this.plane1 = plane1Data;
        this.plane2 = plane2Data;
        this.winner = winner;
        this.advantages = advantages;
        this.canvas = document.getElementById('battleCanvas');
        this.ctx = this.canvas.getContext('2d');
        
        // Animation state
        this.frame = 0;
        this.missiles = [];
        this.explosions = [];
        this.smokeTrails = [];
        
        // Plane positions - start far apart
        this.planePositions = {
            p1: { x: 150, y: 300, angle: 0, speed: 3 },
            p2: { x: 1050, y: 300, angle: 0, speed: 3 }
        };
        
        // Combat phases
        this.phase = 'APPROACH';
        this.phaseTimer = 0;
        
        // Load plane images
        this.loadImages();
    }
    
    loadImages() {
        this.plane1Img = new Image();
        this.plane1Img.src = this.plane1.image;
        
        this.plane2Img = new Image();
        this.plane2Img.src = this.plane2.image;
        
        Promise.all([
            new Promise(resolve => {
                this.plane1Img.onload = resolve;
                this.plane1Img.onerror = () => {
                    console.error('Failed to load plane 1 image');
                    resolve();
                };
            }),
            new Promise(resolve => {
                this.plane2Img.onload = resolve;
                this.plane2Img.onerror = () => {
                    console.error('Failed to load plane 2 image');
                    resolve();
                };
            })
        ]).then(() => {
            this.startAnimation();
        });
    }
    
    startAnimation() {
        this.animate();
    }
    
    updatePhase() {
        this.phaseTimer++;
        
        if (this.phase === 'APPROACH' && this.phaseTimer > 80) {
            this.phase = 'DOGFIGHT';
            this.phaseTimer = 0;
            this.updateCommentary("Aircraft engaging in dogfight!");
        }
        else if (this.phase === 'DOGFIGHT' && this.phaseTimer > 120) {
            this.phase = 'MISSILE';
            this.phaseTimer = 0;
            this.fireMissile();
            this.updateCommentary("Missile launched!");
        }
        else if (this.phase === 'MISSILE' && this.phaseTimer > 80) {
            const loser = this.winner === 'plane1' ? this.plane2 : this.plane1;
            const canEvade = loser.climb_rate > 250 || loser.turn_time < 18;
            
            if (canEvade && Math.random() > 0.7) {
                this.phase = 'EVASION';
                this.phaseTimer = 0;
                this.updateCommentary("Evasive maneuvers!");
                setTimeout(() => this.fireMissile(), 1500);
            } else {
                this.phase = 'EXPLOSION';
                this.phaseTimer = 0;
                this.createExplosion();
                this.updateCommentary("Direct hit confirmed!");
            }
        }
        else if (this.phase === 'EVASION' && this.phaseTimer > 60) {
            this.phase = 'EXPLOSION';
            this.phaseTimer = 0;
            this.createExplosion();
            this.updateCommentary("Impact! " + (this.winner === 'plane1' ? this.plane1.name : this.plane2.name) + " victorious!");
        }
        else if (this.phase === 'EXPLOSION' && this.phaseTimer > 80) {
            this.phase = 'VICTORY';
            this.phaseTimer = 0;
            this.showResults();
        }
    }
    
    fireMissile() {
        const attacker = this.winner === 'plane1' ? 'p1' : 'p2';
        const target = this.winner === 'plane1' ? 'p2' : 'p1';
        const attackerPos = this.planePositions[attacker];
        
        // Missile spawns in front of plane
        // Plane 1 faces right (flipped), so missile goes RIGHT (+)
        // Plane 2 faces left (normal), so missile goes LEFT (-)
        const missileOffset = 60;
        const isPlane1 = attacker === 'p1';
        
        const missileX = isPlane1 ? 
            attackerPos.x + missileOffset : 
            attackerPos.x - missileOffset;
        
        this.missiles.push({
            x: missileX,
            y: attackerPos.y,
            targetX: this.planePositions[target].x,
            targetY: this.planePositions[target].y,
            speed: 10,
            active: true,
            trail: []
        });
    }
    
    createExplosion() {
        const loserPos = this.winner === 'plane1' ? 
            this.planePositions.p2 : 
            this.planePositions.p1;
        
        // Main explosion
        this.explosions.push({
            x: loserPos.x,
            y: loserPos.y,
            radius: 0,
            maxRadius: 120,
            life: 50,
            maxLife: 50
        });
        
        // Debris particles
        for (let i = 0; i < 40; i++) {
            this.explosions.push({
                x: loserPos.x,
                y: loserPos.y,
                vx: (Math.random() - 0.5) * 12,
                vy: (Math.random() - 0.5) * 12,
                size: Math.random() * 6 + 2,
                life: Math.random() * 35 + 15,
                maxLife: Math.random() * 35 + 15,
                isDebris: true
            });
        }
    }
    
    updateCommentary(text) {
        const commentary = document.getElementById('commentaryText');
        if (commentary) {
            commentary.textContent = text;
        }
    }
    
    showResults() {
        const resultsContainer = document.querySelector('.results-container');
        if (resultsContainer) {
            resultsContainer.style.display = 'block';
        }
        document.querySelector('.battle-animation-3d').style.opacity = '0.6';
    }
    
    drawSky() {
        const gradient = this.ctx.createLinearGradient(0, 0, 0, this.canvas.height);
        gradient.addColorStop(0, '#4A90E2');
        gradient.addColorStop(1, '#87CEEB');
        this.ctx.fillStyle = gradient;
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Animated clouds
        this.ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
        const cloudOffset = (this.frame * 0.5) % (this.canvas.width + 200);
        this.drawCloud(cloudOffset - 200, 80, 70);
        this.drawCloud(cloudOffset + 200, 140, 90);
        this.drawCloud(cloudOffset + 600, 100, 80);
    }
    
    drawCloud(x, y, size) {
        this.ctx.beginPath();
        this.ctx.arc(x, y, size * 0.5, 0, Math.PI * 2);
        this.ctx.arc(x + size * 0.5, y, size * 0.6, 0, Math.PI * 2);
        this.ctx.arc(x + size, y, size * 0.5, 0, Math.PI * 2);
        this.ctx.fill();
    }
    
    updatePlanes() {
        const p1 = this.planePositions.p1;
        const p2 = this.planePositions.p2;
        
        if (this.phase === 'APPROACH') {
            // Fly toward each other with slight wobble
            p1.x += 3;
            p2.x -= 3;
            p1.y = 300 + Math.sin(this.frame * 0.04) * 20;
            p2.y = 300 + Math.sin(this.frame * 0.04 + Math.PI) * 20;
            p1.angle = Math.sin(this.frame * 0.03) * 0.05;
            p2.angle = Math.sin(this.frame * 0.03 + Math.PI) * 0.05;
        }
        else if (this.phase === 'DOGFIGHT') {
            // Circular dogfight - REVERSED DIRECTION
            const centerX = 600;
            const centerY = 300;
            const radius = 180;
            
            // Negative angle for counter-clockwise rotation
            const angle1 = -(this.frame * 0.025);
            const angle2 = -(this.frame * 0.025) + Math.PI;
            
            p1.x = centerX + Math.cos(angle1) * radius;
            p1.y = centerY + Math.sin(angle1) * radius;
            p1.angle = angle1 + Math.PI / 2;
            
            p2.x = centerX + Math.cos(angle2) * radius;
            p2.y = centerY + Math.sin(angle2) * radius;
            p2.angle = angle2 + Math.PI / 2;
            
            // Smoke trails
            if (this.frame % 4 === 0) {
                this.smokeTrails.push({ x: p1.x, y: p1.y, life: 30 });
                this.smokeTrails.push({ x: p2.x, y: p2.y, life: 30 });
            }
        }
        else if (this.phase === 'MISSILE' || this.phase === 'EVASION') {
            const winner = this.planePositions[this.winner === 'plane1' ? 'p1' : 'p2'];
            const loser = this.planePositions[this.winner === 'plane1' ? 'p2' : 'p1'];
            
            // Winner pursues
            const dx = loser.x - winner.x;
            const dy = loser.y - winner.y;
            const angle = Math.atan2(dy, dx);
            winner.angle = angle;
            winner.x += Math.cos(angle) * 2.5;
            winner.y += Math.sin(angle) * 2.5;
            
            // Loser evades
            if (this.phase === 'EVASION') {
                loser.x += Math.cos(loser.angle + Math.PI) * 4;
                loser.y += Math.sin(loser.angle + Math.PI) * 4 - 2;
                loser.angle += 0.08;
            } else {
                loser.x += Math.cos(loser.angle) * 2;
                loser.y += Math.sin(loser.angle) * 2;
            }
        }
    }
    
    updateMissiles() {
        this.missiles.forEach(missile => {
            if (!missile.active) return;
            
            const dx = missile.targetX - missile.x;
            const dy = missile.targetY - missile.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            
            if (dist < 25) {
                missile.active = false;
                return;
            }
            
            missile.x += (dx / dist) * missile.speed;
            missile.y += (dy / dist) * missile.speed;
            
            const target = this.winner === 'plane1' ? 'p2' : 'p1';
            missile.targetX = this.planePositions[target].x;
            missile.targetY = this.planePositions[target].y;
            
            missile.trail.push({ x: missile.x, y: missile.y, life: 15 });
            if (missile.trail.length > 12) missile.trail.shift();
        });
    }
    
    updateExplosions() {
        this.explosions.forEach(exp => {
            if (exp.isDebris) {
                exp.x += exp.vx;
                exp.y += exp.vy;
                exp.vy += 0.25;
                exp.vx *= 0.98;
                exp.life--;
            } else {
                exp.radius += (exp.maxRadius - exp.radius) * 0.12;
                exp.life--;
            }
        });
        
        this.explosions = this.explosions.filter(exp => exp.life > 0);
    }
    
    updateSmokeTrails() {
        this.smokeTrails.forEach(smoke => smoke.life--);
        this.smokeTrails = this.smokeTrails.filter(smoke => smoke.life > 0);
    }
    
    drawPlane(img, x, y, angle, isPlane1 = false) {
        this.ctx.save();
        this.ctx.translate(x, y);
        this.ctx.rotate(angle);
        
        // Fixed scale for consistent size
        const scale = 0.12;
        
        // Plane 1 faces RIGHT (flip horizontally)
        // Plane 2 faces LEFT (normal)
        if (isPlane1) {
            this.ctx.scale(-scale, scale);
        } else {
            this.ctx.scale(scale, scale);
        }
        
        this.ctx.drawImage(img, -img.width / 2, -img.height / 2);
        this.ctx.restore();
    }
    
    drawMissiles() {
        this.missiles.forEach(missile => {
            if (!missile.active) return;
            
            // Trail
            missile.trail.forEach((point, i) => {
                const alpha = (point.life / 15) * 0.7;
                this.ctx.fillStyle = `rgba(255, 120, 30, ${alpha})`;
                this.ctx.fillRect(point.x - 3, point.y - 3, 6, 6);
                point.life--;
            });
            
            // Missile body
            this.ctx.save();
            this.ctx.translate(missile.x, missile.y);
            const angle = Math.atan2(missile.targetY - missile.y, missile.targetX - missile.x);
            this.ctx.rotate(angle);
            
            this.ctx.fillStyle = '#555';
            this.ctx.fillRect(-12, -3, 24, 6);
            
            this.ctx.fillStyle = '#777';
            this.ctx.beginPath();
            this.ctx.moveTo(12, 0);
            this.ctx.lineTo(18, -4);
            this.ctx.lineTo(18, 4);
            this.ctx.fill();
            
            // Exhaust
            this.ctx.fillStyle = `rgba(255, ${80 + Math.random() * 175}, 0, 0.9)`;
            this.ctx.fillRect(-18, -4, 10, 8);
            
            this.ctx.restore();
        });
    }
    
    drawExplosions() {
        this.explosions.forEach(exp => {
            if (exp.isDebris) {
                const alpha = exp.life / exp.maxLife;
                this.ctx.fillStyle = `rgba(80, 80, 80, ${alpha})`;
                this.ctx.fillRect(exp.x - exp.size / 2, exp.y - exp.size / 2, exp.size, exp.size);
            } else {
                const progress = 1 - (exp.life / exp.maxLife);
                const alpha = exp.life / exp.maxLife;
                
                // Outer ring - red
                this.ctx.fillStyle = `rgba(255, 60, 0, ${alpha * 0.7})`;
                this.ctx.beginPath();
                this.ctx.arc(exp.x, exp.y, exp.radius, 0, Math.PI * 2);
                this.ctx.fill();
                
                // Middle ring - orange
                this.ctx.fillStyle = `rgba(255, 140, 0, ${alpha * 0.85})`;
                this.ctx.beginPath();
                this.ctx.arc(exp.x, exp.y, exp.radius * 0.7, 0, Math.PI * 2);
                this.ctx.fill();
                
                // Core - yellow
                this.ctx.fillStyle = `rgba(255, 255, 80, ${alpha})`;
                this.ctx.beginPath();
                this.ctx.arc(exp.x, exp.y, exp.radius * 0.4, 0, Math.PI * 2);
                this.ctx.fill();
            }
        });
    }
    
    drawSmokeTrails() {
        this.smokeTrails.forEach(smoke => {
            const alpha = (smoke.life / 30) * 0.25;
            this.ctx.fillStyle = `rgba(200, 200, 200, ${alpha})`;
            this.ctx.beginPath();
            this.ctx.arc(smoke.x, smoke.y, 6, 0, Math.PI * 2);
            this.ctx.fill();
        });
    }
    
    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        this.drawSky();
        this.drawSmokeTrails();
        
        if (this.phase !== 'EXPLOSION' && this.phase !== 'VICTORY') {
            this.updatePlanes();
        }
        
        this.updateMissiles();
        this.updateExplosions();
        this.updateSmokeTrails();
        
        this.drawMissiles();
        
        // Draw planes
        if (this.phase !== 'EXPLOSION' && this.phase !== 'VICTORY') {
            this.drawPlane(this.plane1Img, this.planePositions.p1.x, this.planePositions.p1.y, this.planePositions.p1.angle, true);
            this.drawPlane(this.plane2Img, this.planePositions.p2.x, this.planePositions.p2.y, this.planePositions.p2.angle, false);
        } else {
            // Only draw winner
            if (this.winner === 'plane1') {
                this.drawPlane(this.plane1Img, this.planePositions.p1.x, this.planePositions.p1.y, this.planePositions.p1.angle, true);
            } else {
                this.drawPlane(this.plane2Img, this.planePositions.p2.x, this.planePositions.p2.y, this.planePositions.p2.angle, false);
            }
        }
        
        this.drawExplosions();
        
        this.updatePhase();
        this.frame++;
        
        if (this.phase !== 'VICTORY') {
            requestAnimationFrame(() => this.animate());
        }
    }
}