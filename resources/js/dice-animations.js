/**
 * Enhanced Dice Rolling Animations
 * Provides realistic 3D dice rolling effects for Yahtzee and other dice games
 */

import { gsap } from 'gsap';
import { GameEnhancements } from './game-enhancements.js';

class DiceAnimationController {
    constructor() {
        this.animationService = GameEnhancements.getAnimation();
        this.audioService = GameEnhancements.getAudio();
        this.isRolling = false;
    }

    /**
     * Roll a single die with enhanced animation
     */
    async rollDie(dieElement, finalValue, options = {}) {
        if (this.isRolling) return;
        
        this.isRolling = true;
        const duration = options.duration || 1.5;
        const intensity = options.intensity || 'medium';
        
        // Play dice rolling sound
        this.audioService.playDiceRoll();
        
        // Create timeline for complex animation
        const tl = gsap.timeline();
        
        // Phase 1: Initial shake and scale
        tl.to(dieElement, {
            duration: 0.2,
            scale: 1.2,
            rotation: "+=180",
            ease: "power2.out"
        });
        
        // Phase 2: Rapid spinning (simulating rolling)
        const spinCount = intensity === 'high' ? 8 : intensity === 'medium' ? 6 : 4;
        tl.to(dieElement, {
            duration: duration * 0.6,
            rotation: `+=${360 * spinCount}`,
            scale: 1.1,
            ease: "power2.inOut"
        });
        
        // Phase 3: Slow down and settle
        tl.to(dieElement, {
            duration: duration * 0.4,
            rotation: "+=180",
            scale: 1,
            ease: "elastic.out(1, 0.3)"
        });
        
        // Update dice face during animation
        tl.call(() => {
            this.updateDiceFace(dieElement, finalValue);
        }, null, duration * 0.8);
        
        // Add bounce effect
        tl.to(dieElement, {
            duration: 0.1,
            y: -10,
            ease: "power2.out"
        })
        .to(dieElement, {
            duration: 0.2,
            y: 0,
            ease: "bounce.out"
        });
        
        // Complete animation
        tl.call(() => {
            this.isRolling = false;
            if (options.onComplete) options.onComplete();
        });
        
        return tl;
    }

    /**
     * Roll multiple dice with staggered timing
     */
    async rollMultipleDice(diceElements, finalValues, options = {}) {
        if (this.isRolling) return;
        
        this.isRolling = true;
        const stagger = options.stagger || 0.1;
        const duration = options.duration || 1.5;
        
        // Play enhanced dice rolling sound
        this.audioService.playDiceRoll();
        
        const tl = gsap.timeline();
        
        // Stagger the rolling animations
        diceElements.forEach((die, index) => {
            tl.to(die, {
                duration: 0.2,
                scale: 1.2,
                rotation: "+=180",
                ease: "power2.out"
            }, index * stagger);
            
            tl.to(die, {
                duration: duration * 0.6,
                rotation: `+=${360 * 6}`,
                scale: 1.1,
                ease: "power2.inOut"
            }, index * stagger);
            
            tl.to(die, {
                duration: duration * 0.4,
                rotation: "+=180",
                scale: 1,
                ease: "elastic.out(1, 0.3)"
            }, index * stagger);
            
            // Update dice face
            tl.call(() => {
                this.updateDiceFace(die, finalValues[index]);
            }, null, (index * stagger) + (duration * 0.8));
            
            // Bounce effect
            tl.to(die, {
                duration: 0.1,
                y: -10,
                ease: "power2.out"
            }, (index * stagger) + (duration * 0.9));
            
            tl.to(die, {
                duration: 0.2,
                y: 0,
                ease: "bounce.out"
            }, (index * stagger) + (duration * 0.9) + 0.1);
        });
        
        tl.call(() => {
            this.isRolling = false;
            if (options.onComplete) options.onComplete();
        });
        
        return tl;
    }

    /**
     * Animate dice selection/holding
     */
    selectDie(dieElement, isSelected) {
        const tl = gsap.timeline();
        
        if (isSelected) {
            // Select animation
            tl.to(dieElement, {
                duration: 0.3,
                scale: 1.1,
                rotation: "+=10",
                boxShadow: "0 0 20px rgba(34, 197, 94, 0.5)",
                ease: "elastic.out(1, 0.3)"
            });
        } else {
            // Deselect animation
            tl.to(dieElement, {
                duration: 0.3,
                scale: 1,
                rotation: 0,
                boxShadow: "0 2px 8px rgba(0, 0, 0, 0.1)",
                ease: "power2.out"
            });
        }
        
        return tl;
    }

    /**
     * Animate dice reset (for new game)
     */
    resetDice(diceElements) {
        const tl = gsap.timeline();
        
        tl.to(diceElements, {
            duration: 0.5,
            scale: 0.8,
            opacity: 0.7,
            ease: "power2.out"
        })
        .to(diceElements, {
            duration: 0.3,
            scale: 1,
            opacity: 1,
            ease: "elastic.out(1, 0.3)"
        });
        
        return tl;
    }

    /**
     * Animate score highlight
     */
    highlightScore(scoreElement, score) {
        const tl = gsap.timeline();
        
        tl.to(scoreElement, {
            duration: 0.3,
            scale: 1.2,
            color: "#10b981",
            ease: "power2.out"
        })
        .to(scoreElement, {
            duration: 0.3,
            scale: 1,
            color: "",
            ease: "power2.out"
        });
        
        return tl;
    }

    /**
     * Update dice face with smooth transition
     */
    updateDiceFace(dieElement, value) {
        const img = dieElement.querySelector('img');
        if (!img) return;
        
        // Create new image element for smooth transition
        const newImg = document.createElement('img');
        newImg.src = `/images/Dice/die_${value}.png`;
        newImg.alt = `Die showing ${value}`;
        newImg.className = img.className;
        
        // Replace with fade effect
        gsap.set(newImg, { opacity: 0 });
        dieElement.replaceChild(newImg, img);
        
        gsap.to(newImg, {
            duration: 0.3,
            opacity: 1,
            ease: "power2.out"
        });
    }

    /**
     * Create dice rolling container with 3D effect
     */
    createDiceContainer(element) {
        const container = document.createElement('div');
        container.className = 'dice-container';
        container.style.cssText = `
            perspective: 1000px;
            transform-style: preserve-3d;
            display: inline-block;
        `;
        
        element.parentNode.insertBefore(container, element);
        container.appendChild(element);
        
        return container;
    }

    /**
     * Add 3D rotation effect to dice
     */
    add3DEffect(dieElement) {
        gsap.set(dieElement, {
            transformStyle: "preserve-3d",
            transformOrigin: "center center"
        });
    }

    /**
     * Animate dice celebration (for good rolls)
     */
    celebrateRoll(diceElements, isGoodRoll = false) {
        const tl = gsap.timeline();
        
        if (isGoodRoll) {
            // Good roll celebration
            tl.to(diceElements, {
                duration: 0.3,
                scale: 1.2,
                rotation: "+=360",
                ease: "elastic.out(1, 0.3)"
            })
            .to(diceElements, {
                duration: 0.2,
                scale: 1,
                rotation: 0,
                ease: "power2.out"
            });
            
            // Add glow effect
            tl.to(diceElements, {
                duration: 0.5,
                boxShadow: "0 0 30px rgba(34, 197, 94, 0.8)",
                ease: "power2.inOut"
            })
            .to(diceElements, {
                duration: 0.5,
                boxShadow: "0 2px 8px rgba(0, 0, 0, 0.1)",
                ease: "power2.inOut"
            });
        } else {
            // Regular roll completion
            tl.to(diceElements, {
                duration: 0.2,
                scale: 1.05,
                ease: "power2.out"
            })
            .to(diceElements, {
                duration: 0.2,
                scale: 1,
                ease: "power2.out"
            });
        }
        
        return tl;
    }
}

// Create global dice animation controller
window.DiceAnimations = new DiceAnimationController();

// Export for module usage
export default DiceAnimationController;
