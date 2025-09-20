/**
 * Enhanced Card Animations
 * Provides realistic card dealing, flipping, and movement effects for card games
 */

import { gsap } from 'gsap';
import { GameEnhancements } from './game-enhancements.js';

class CardAnimationController {
    constructor() {
        this.animationService = GameEnhancements.getAnimation();
        this.audioService = GameEnhancements.getAudio();
        this.isAnimating = false;
    }

    /**
     * Deal cards with realistic dealing animation
     */
    async dealCards(cardElements, targetPositions, options = {}) {
        if (this.isAnimating) return;
        
        this.isAnimating = true;
        const stagger = options.stagger || 0.1;
        const duration = options.duration || 0.8;
        
        // Play card dealing sound
        this.audioService.playCardDeal();
        
        const tl = gsap.timeline();
        
        // Set initial positions (cards start from deck)
        cardElements.forEach((card, index) => {
            gsap.set(card, {
                x: options.deckPosition?.x || 0,
                y: options.deckPosition?.y || 0,
                rotation: 0,
                scale: 0.8,
                opacity: 0
            });
        });
        
        // Stagger the dealing animations
        cardElements.forEach((card, index) => {
            const targetPos = targetPositions[index];
            
            tl.to(card, {
                duration: 0.2,
                opacity: 1,
                scale: 0.9,
                ease: "power2.out"
            }, index * stagger);
            
            tl.to(card, {
                duration: duration,
                x: targetPos.x,
                y: targetPos.y,
                rotation: targetPos.rotation || 0,
                scale: 1,
                ease: "power2.out"
            }, index * stagger);
        });
        
        tl.call(() => {
            this.isAnimating = false;
            if (options.onComplete) options.onComplete();
        });
        
        return tl;
    }

    /**
     * Flip card from face down to face up
     */
    async flipCard(cardElement, newCardData, options = {}) {
        if (this.isAnimating) return;
        
        this.isAnimating = true;
        const duration = options.duration || 0.6;
        
        // Play card flip sound
        this.audioService.playCardFlip();
        
        const tl = gsap.timeline();
        
        // Phase 1: Scale down horizontally
        tl.to(cardElement, {
            duration: duration / 2,
            scaleX: 0,
            ease: "power2.inOut"
        });
        
        // Phase 2: Update card content
        tl.call(() => {
            this.updateCardContent(cardElement, newCardData);
        });
        
        // Phase 3: Scale back up horizontally
        tl.to(cardElement, {
            duration: duration / 2,
            scaleX: 1,
            ease: "power2.inOut"
        });
        
        tl.call(() => {
            this.isAnimating = false;
            if (options.onComplete) options.onComplete();
        });
        
        return tl;
    }

    /**
     * Animate card selection/highlighting
     */
    selectCard(cardElement, isSelected, options = {}) {
        const tl = gsap.timeline();
        
        if (isSelected) {
            // Select animation
            tl.to(cardElement, {
                duration: 0.3,
                scale: 1.1,
                y: -10,
                rotation: options.rotation || 5,
                boxShadow: "0 8px 25px rgba(34, 197, 94, 0.4)",
                ease: "elastic.out(1, 0.3)"
            });
        } else {
            // Deselect animation
            tl.to(cardElement, {
                duration: 0.3,
                scale: 1,
                y: 0,
                rotation: 0,
                boxShadow: "0 2px 8px rgba(0, 0, 0, 0.1)",
                ease: "power2.out"
            });
        }
        
        return tl;
    }

    /**
     * Animate card playing (from hand to discard pile)
     */
    async playCard(cardElement, discardPosition, options = {}) {
        if (this.isAnimating) return;
        
        this.isAnimating = true;
        const duration = options.duration || 0.8;
        
        // Play move sound
        this.audioService.playMove();
        
        const tl = gsap.timeline();
        
        // Get current position
        const currentPos = gsap.getProperty(cardElement, "transform");
        
        // Phase 1: Lift card slightly
        tl.to(cardElement, {
            duration: 0.2,
            scale: 1.1,
            y: -15,
            ease: "power2.out"
        });
        
        // Phase 2: Move to discard pile
        tl.to(cardElement, {
            duration: duration,
            x: discardPosition.x,
            y: discardPosition.y,
            rotation: discardPosition.rotation || 0,
            scale: 1,
            ease: "power2.inOut"
        });
        
        // Phase 3: Settle in discard pile
        tl.to(cardElement, {
            duration: 0.2,
            scale: 1.05,
            ease: "power2.out"
        })
        .to(cardElement, {
            duration: 0.2,
            scale: 1,
            ease: "power2.out"
        });
        
        tl.call(() => {
            this.isAnimating = false;
            if (options.onComplete) options.onComplete();
        });
        
        return tl;
    }

    /**
     * Animate card drawing (from deck to hand)
     */
    async drawCard(cardElement, handPosition, options = {}) {
        if (this.isAnimating) return;
        
        this.isAnimating = true;
        const duration = options.duration || 0.6;
        
        // Play card dealing sound
        this.audioService.playCardDeal();
        
        const tl = gsap.timeline();
        
        // Set initial position (from deck)
        gsap.set(cardElement, {
            x: options.deckPosition?.x || 0,
            y: options.deckPosition?.y || 0,
            rotation: 0,
            scale: 0.8,
            opacity: 0
        });
        
        // Draw animation
        tl.to(cardElement, {
            duration: 0.2,
            opacity: 1,
            scale: 0.9,
            ease: "power2.out"
        })
        .to(cardElement, {
            duration: duration,
            x: handPosition.x,
            y: handPosition.y,
            rotation: handPosition.rotation || 0,
            scale: 1,
            ease: "power2.out"
        });
        
        tl.call(() => {
            this.isAnimating = false;
            if (options.onComplete) options.onComplete();
        });
        
        return tl;
    }

    /**
     * Animate card shuffling
     */
    async shuffleCards(cardElements, options = {}) {
        if (this.isAnimating) return;
        
        this.isAnimating = true;
        const duration = options.duration || 1.0;
        
        const tl = gsap.timeline();
        
        // Shuffle animation - cards move around randomly
        cardElements.forEach((card, index) => {
            tl.to(card, {
                duration: duration / 3,
                x: `+=${(Math.random() - 0.5) * 100}`,
                y: `+=${(Math.random() - 0.5) * 50}`,
                rotation: `+=${(Math.random() - 0.5) * 180}`,
                ease: "power2.inOut"
            }, index * 0.05);
            
            tl.to(card, {
                duration: duration / 3,
                x: `+=${(Math.random() - 0.5) * 100}`,
                y: `+=${(Math.random() - 0.5) * 50}`,
                rotation: `+=${(Math.random() - 0.5) * 180}`,
                ease: "power2.inOut"
            }, (index * 0.05) + (duration / 3));
            
            tl.to(card, {
                duration: duration / 3,
                x: 0,
                y: 0,
                rotation: 0,
                ease: "power2.out"
            }, (index * 0.05) + (duration * 2 / 3));
        });
        
        tl.call(() => {
            this.isAnimating = false;
            if (options.onComplete) options.onComplete();
        });
        
        return tl;
    }

    /**
     * Animate win celebration with cards
     */
    celebrateWin(cardElements, options = {}) {
        const tl = gsap.timeline();
        
        // Play win sound
        this.audioService.playWin();
        
        // Stagger celebration animations
        cardElements.forEach((card, index) => {
            tl.to(card, {
                duration: 0.5,
                scale: 1.2,
                rotation: "+=360",
                y: -20,
                ease: "elastic.out(1, 0.3)"
            }, index * 0.1);
            
            tl.to(card, {
                duration: 0.3,
                scale: 1,
                rotation: 0,
                y: 0,
                ease: "power2.out"
            }, (index * 0.1) + 0.5);
        });
        
        return tl;
    }

    /**
     * Update card content with smooth transition
     */
    updateCardContent(cardElement, cardData) {
        const rankElement = cardElement.querySelector('.card-rank');
        const suitElement = cardElement.querySelector('.card-suit');
        
        if (rankElement) {
            gsap.to(rankElement, {
                duration: 0.1,
                opacity: 0,
                ease: "power2.inOut",
                onComplete: () => {
                    rankElement.textContent = cardData.rank;
                    gsap.to(rankElement, {
                        duration: 0.1,
                        opacity: 1,
                        ease: "power2.inOut"
                    });
                }
            });
        }
        
        if (suitElement) {
            gsap.to(suitElement, {
                duration: 0.1,
                opacity: 0,
                ease: "power2.inOut",
                onComplete: () => {
                    suitElement.textContent = this.getSuitSymbol(cardData.suit);
                    gsap.to(suitElement, {
                        duration: 0.1,
                        opacity: 1,
                        ease: "power2.inOut"
                    });
                }
            });
        }
        
        // Update card class for styling
        cardElement.className = `card ${cardData.suit}`;
    }

    /**
     * Get suit symbol for cards
     */
    getSuitSymbol(suit) {
        const symbols = {
            'hearts': '♥',
            'diamonds': '♦',
            'clubs': '♣',
            'spades': '♠'
        };
        return symbols[suit] || '?';
    }

    /**
     * Create card container with 3D effects
     */
    createCardContainer(element) {
        const container = document.createElement('div');
        container.className = 'card-container';
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
     * Add hover effects to cards
     */
    addHoverEffects(cardElement) {
        cardElement.addEventListener('mouseenter', () => {
            if (!this.isAnimating) {
                gsap.to(cardElement, {
                    duration: 0.3,
                    scale: 1.05,
                    y: -5,
                    ease: "power2.out"
                });
            }
        });
        
        cardElement.addEventListener('mouseleave', () => {
            if (!this.isAnimating) {
                gsap.to(cardElement, {
                    duration: 0.3,
                    scale: 1,
                    y: 0,
                    ease: "power2.out"
                });
            }
        });
    }

    /**
     * Animate card stack (for deck visualization)
     */
    animateCardStack(stackElement, cardCount) {
        const tl = gsap.timeline();
        
        // Create visual stack effect
        tl.to(stackElement, {
            duration: 0.5,
            scale: 1 + (cardCount * 0.01),
            boxShadow: `0 ${cardCount * 2}px ${cardCount * 4}px rgba(0, 0, 0, 0.3)`,
            ease: "power2.out"
        });
        
        return tl;
    }
}

// Create global card animation controller
window.CardAnimations = new CardAnimationController();

// Export for module usage
export default CardAnimationController;
