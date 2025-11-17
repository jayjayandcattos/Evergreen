// Enhanced Points System with Redemption
class PointsSystem {
    constructor(apiUrl = '../points_api.php') {
        this.apiUrl = apiUrl;
        this.totalPoints = 0;
    }

    // Load user's total points
    async loadUserPoints() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_user_points`);
            const data = await response.json();
            
            if (data.success) {
                this.totalPoints = parseFloat(data.total_points);
                this.updatePointsDisplay();
                return this.totalPoints;
            }
        } catch (error) {
            console.error('Error loading points:', error);
        }
        return 0;
    }

    // Update points display with animation
    updatePointsDisplay() {
        const pointsElements = document.querySelectorAll('#totalPoints, .points-number');
        pointsElements.forEach(el => {
            const currentValue = parseFloat(el.textContent) || 0;
            const targetValue = this.totalPoints;
            this.animateValue(el, currentValue, targetValue, 600);
        });
    }

    // Animate number counting
    animateValue(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = current.toFixed(2);
        }, 16);
    }

    // Redeem reward with points
    async redeemReward(rewardName, pointsCost) {
        // Check if user has enough points
        if (this.totalPoints < pointsCost) {
            this.showErrorMessage(`Insufficient points! You need ${pointsCost} points but only have ${this.totalPoints.toFixed(2)} points.`);
            return false;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'redeem_reward');
            formData.append('reward_name', rewardName);
            formData.append('points_cost', pointsCost);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update total points
                this.totalPoints = parseFloat(data.new_total);
                this.updatePointsDisplay();
                
                // Show success message
                this.showRedemptionSuccess(rewardName, pointsCost);
                
                return true;
            } else {
                this.showErrorMessage(data.message || 'Failed to redeem reward');
                return false;
            }
        } catch (error) {
            console.error('Error redeeming reward:', error);
            this.showErrorMessage('An error occurred. Please try again.');
            return false;
        }
    }

    // Load available missions
    async loadMissions() {
        try {
            console.log('Loading missions from:', `${this.apiUrl}?action=get_missions`);
            const response = await fetch(`${this.apiUrl}?action=get_missions`);
            
            if (!response.ok) {
                console.error('API response not OK:', response.status, response.statusText);
                return [];
            }
            
            const text = await response.text();
            console.log('Missions API raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON response:', e);
                console.error('Response text:', text);
                return [];
            }
            
            console.log('Missions API parsed response:', data);
            
            if (data.success) {
                console.log('Missions loaded:', data.missions ? data.missions.length : 0, 'missions');
                if (data.debug) {
                    console.log('Debug info:', data.debug);
                }
                return data.missions || [];
            } else {
                console.error('API returned error:', data.message || 'Unknown error');
                if (data.debug) {
                    console.error('Debug info:', data.debug);
                }
                return [];
            }
        } catch (error) {
            console.error('Error loading missions:', error);
            return [];
        }
    }

    // Collect mission points
    async collectMission(missionId, buttonElement) {
        buttonElement.disabled = true;
        buttonElement.textContent = 'Collecting...';
        
        try {
            const formData = new FormData();
            formData.append('action', 'collect_mission');
            formData.append('mission_id', missionId);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.totalPoints = parseFloat(data.total_points);
                this.updatePointsDisplay();
                
                const missionCard = buttonElement.closest('.mission-card');
                this.showSuccessMessage(data.points_earned);
                this.removeMissionCard(missionCard);
                
                this.renderPointHistory('history');
                this.renderCompletedMissions('completed');
                
                return data;
            } else {
                buttonElement.disabled = false;
                buttonElement.textContent = 'Collect';
                this.showErrorMessage(data.message || 'Failed to collect mission');
                return null;
            }
        } catch (error) {
            console.error('Error collecting mission:', error);
            buttonElement.disabled = false;
            buttonElement.textContent = 'Collect';
            this.showErrorMessage('An error occurred. Please try again.');
            return null;
        }
    }

    // Remove mission card with animation
    removeMissionCard(missionCard) {
        missionCard.style.transition = 'all 0.5s ease';
        missionCard.style.opacity = '0';
        missionCard.style.transform = 'translateX(100px)';
        
        setTimeout(() => {
            missionCard.remove();
            
            const container = document.getElementById('mission');
            if (container) {
                const remainingMissions = container.querySelectorAll('.mission-card');
                
                if (remainingMissions.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">🎉</div>
                            <div class="empty-state-text">All missions collected!</div>
                        </div>
                    `;
                }
            }
        }, 500);
    }

    // Load point history
    async loadPointHistory() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_point_history`);
            const data = await response.json();
            
            if (data.success) {
                return data.history;
            }
        } catch (error) {
            console.error('Error loading history:', error);
        }
        return [];
    }

    // Load completed missions
    async loadCompletedMissions() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_completed_missions`);
            const data = await response.json();
            
            if (data.success) {
                return data.completed;
            }
        } catch (error) {
            console.error('Error loading completed missions:', error);
        }
        return [];
    }

    // Show redemption success message
    showRedemptionSuccess(rewardName, pointsCost) {
        const modal = document.querySelector('.modal-container');
        const successTxt = document.querySelector('.success-txt');
        const txt = document.querySelector('.txtrewards');
        const okBtn = document.querySelector('.ok');
        
        if (modal && successTxt && txt) {
            modal.style.display = 'flex';
            successTxt.textContent = 'Redeemed';
            txt.textContent = rewardName;
            
            // Update text to show points deducted
            if (!document.querySelector('.points-deducted')) {
                const pointsDeducted = document.createElement('p');
                pointsDeducted.className = 'points-deducted';
                pointsDeducted.style.cssText = 'color: #dc3545; font-size: 14px; margin-top: 10px; font-weight: 600;';
                pointsDeducted.textContent = `-${pointsCost} points`;
                txt.parentElement.insertBefore(pointsDeducted, okBtn);
            } else {
                document.querySelector('.points-deducted').textContent = `-${pointsCost} points`;
            }
            
            okBtn.onclick = () => {
                modal.style.display = 'none';
                // Reload history to show deduction
                this.renderPointHistory('history-preview');
            };
        }
    }

    // Show success message
    showSuccessMessage(points) {
        const successMsg = document.createElement('div');
        successMsg.innerHTML = `
            <div style="font-size: 48px; margin-bottom: 10px;">✓</div>
            <div style="font-size: 20px; font-weight: 700;">Success!</div>
            <div style="font-size: 16px; margin-top: 5px;">+${points} points collected</div>
        `;
        successMsg.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #0d4d3d 0%, #1a6b56 100%);
            color: white;
            padding: 30px 50px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            z-index: 10000;
            animation: successPop 2.5s ease;
        `;
        document.body.appendChild(successMsg);
        
        setTimeout(() => {
            successMsg.remove();
        }, 2500);
    }

    // Show error message
    showErrorMessage(message) {
        const errorMsg = document.createElement('div');
        errorMsg.innerHTML = `
            <div style="font-size: 40px; margin-bottom: 8px;">⚠</div>
            <div style="font-size: 16px; font-weight: 600;">${message}</div>
        `;
        errorMsg.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 25px 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            z-index: 10000;
            animation: errorShake 2s ease;
        `;
        document.body.appendChild(errorMsg);
        
        setTimeout(() => {
            errorMsg.remove();
        }, 2000);
    }

    // Render missions
    async renderMissions(containerId) {
        console.log('renderMissions called for container:', containerId);
        const missions = await this.loadMissions();
        console.log('Missions to render:', missions);
        const container = document.getElementById(containerId);
        
        if (!container) {
            console.error('Container not found:', containerId);
            return;
        }
        
        container.innerHTML = '';
        
        if (missions.length === 0) {
            console.log('No missions to display');
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">🎉</div>
                    <div class="empty-state-text">All missions collected!</div>
                </div>
            `;
            return;
        }
        
        console.log('Rendering', missions.length, 'missions');
        
        missions.forEach(mission => {
            const card = document.createElement('div');
            card.className = 'mission-card';
            card.dataset.missionId = mission.id;
            
            const statusBadge = mission.status === 'pending' 
                ? '<span style="color:#ff9800;font-size:12px;font-weight:600;">⏳ Pending</span>'
                : '<span style="color:#4caf50;font-size:12px;font-weight:600;">✓ Available</span>';
            
            card.innerHTML = `
                <div class="mission-timestamp">${statusBadge}</div>
                <div class="mission-points">
                    <div class="mission-points-value">${parseFloat(mission.points_value).toFixed(2)}</div>
                    <div class="mission-points-label">points</div>
                </div>
                <div class="mission-divider"></div>
                <div class="mission-details">
                    <div class="mission-description">${mission.mission_text}</div>
                    <div class="mission-actions">
                        <button class="collect-btn" 
                                onclick="pointsSystem.collectMission(${mission.id}, this)"
                                ${mission.status === 'pending' ? 'disabled' : ''}>
                            ${mission.status === 'pending' ? 'Locked' : 'Collect'}
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(card);
        });
    }

    // renderPointHistory
        async renderPointHistory(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            const history = await this.loadPointHistory();
            
            if (history.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📊</div>
                        <div class="empty-state-text">No point history yet</div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = '';
            
            // Display all history items
            history.forEach(item => {
                const card = document.createElement('div');
                card.className = 'mission-card';
                
                // Check if it's a deduction (negative points)
                const points = parseFloat(item.points);
                const isDeduction = points < 0;
                const pointsColor = isDeduction ? '#dc3545' : '#28a745';
                const pointsSign = isDeduction ? '' : '+';
                const badgeText = isDeduction ? 'Redeemed' : 'Collected';
                
                card.innerHTML = `
                    <div class="mission-timestamp">${item.timestamp}</div>
                    <div class="mission-points">
                        <div class="mission-points-value" style="color: ${pointsColor};">${pointsSign}${Math.abs(points).toFixed(2)}</div>
                        <div class="mission-points-label">points</div>
                    </div>
                    <div class="mission-divider"></div>
                    <div class="mission-details">
                        <div class="mission-description">${item.description}</div>
                        <div class="completed-badge">${badgeText}</div>
                    </div>
                `;
                container.appendChild(card);
            });
        }

    // Render completed missions
    async renderCompletedMissions(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const completed = await this.loadCompletedMissions();
        
        if (completed.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">✓</div>
                    <div class="empty-state-text">No completed missions yet</div>
                </div>
            `;
            return;
        }
        
        container.innerHTML = '';
        completed.forEach(item => {
            const card = document.createElement('div');
            card.className = 'mission-card';
            card.innerHTML = `
                <div class="mission-timestamp">${item.timestamp}</div>
                <div class="mission-points">
                    <div class="mission-points-value">${item.points}</div>
                    <div class="mission-points-label">points</div>
                </div>
                <div class="mission-divider"></div>
                <div class="mission-details">
                    <div class="mission-description">${item.description}</div>
                    <div class="completed-badge">✓ Completed</div>
                </div>
            `;
            container.appendChild(card);
        });
    }
}

// Global instance
const pointsSystem = new PointsSystem('/points_api.php');

// Initialize on page load
document.addEventListener('DOMContentLoaded', async function() {
    await pointsSystem.loadUserPoints();
    
    const missionContainer = document.getElementById('mission');
    if (missionContainer) {
        await pointsSystem.renderMissions('mission');
    }
    
    // Add CSS animations
    if (!document.getElementById('points-system-styles')) {
        const style = document.createElement('style');
        style.id = 'points-system-styles';
        style.textContent = `
            @keyframes successPop {
                0% { opacity: 0; transform: translate(-50%, -50%) scale(0.5); }
                15% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
                25% { transform: translate(-50%, -50%) scale(1); }
                85% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                100% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
            }
            
            @keyframes errorShake {
                0%, 100% { transform: translate(-50%, -50%); opacity: 0; }
                10% { opacity: 1; transform: translate(-50%, -50%); }
                20%, 40%, 60% { transform: translate(-45%, -50%); }
                30%, 50%, 70% { transform: translate(-55%, -50%); }
                80% { opacity: 1; transform: translate(-50%, -50%); }
            }
        `;
        document.head.appendChild(style);
    }
});