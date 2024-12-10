// mercure-client.js
class AuctionMercureClient {
    constructor() {
        // Récupération de la configuration depuis les data attributes
        const config = document.getElementById('auction-config');
        if (!config) {
            throw new Error('Configuration element not found');
        }

        // Initialisation des propriétés depuis les data attributes
        this.mercureUrl = config.dataset.mercureUrl;
        this.subscriptionToken = config.dataset.subscriptionToken;
        this.auctionId = config.dataset.auctionId;
        this.minimumIncrement = parseFloat(config.dataset.minimumIncrement);
        this.minimumNextBid = parseFloat(config.dataset.minimumNextBid);

        // Autres initialisations
        this.eventSource = null;
        this.onBidUpdateCallbacks = [];
        this.notificationsEnabled = false;

        // Bind des méthodes
        this.handleBid = this.handleBid.bind(this);
        this.handleError = this.handleError.bind(this);
        this.requestNotificationPermission = this.requestNotificationPermission.bind(this);
        this.handleBidSubmit = this.handleBidSubmit.bind(this);

        // Initialisation
        this.addNotificationButton();
        this.initBidForm();
    }

    addNotificationButton() {
        const container = document.querySelector('.auction-controls') || document.createElement('div');
        if (!container.classList.contains('auction-controls')) {
            container.classList.add('auction-controls');
            document.querySelector('#bid-form')?.parentNode.insertBefore(container, document.querySelector('#bid-form'));
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.classList.add('btn', 'btn-secondary', 'mb-3');
        button.textContent = 'Activer les notifications';
        button.onclick = this.requestNotificationPermission;

        container.appendChild(button);
    }

    async requestNotificationPermission() {
        if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            this.notificationsEnabled = permission === 'granted';

            // Mettre à jour l'interface utilisateur
            const button = document.querySelector('.auction-controls button');
            if (button) {
                button.textContent = this.notificationsEnabled ?
                    'Notifications activées' :
                    'Activer les notifications';
                button.disabled = this.notificationsEnabled;
            }
        }
    }

    initBidForm() {
        const bidForm = document.getElementById('bid-form');
        if (bidForm) {
            bidForm.addEventListener('submit', this.handleBidSubmit);
        }
    }

    connect() {
        try {
            const url = new URL(this.mercureUrl);
            url.searchParams.append('topic', `auction/${this.auctionId}`);

            this.eventSource = new EventSource(url, {
                headers: {
                    'Authorization': `Bearer ${this.subscriptionToken}`
                }
            });

            this.eventSource.onmessage = this.handleBid;
            this.eventSource.onerror = this.handleError;

            console.log('Connected to Mercure hub');
        } catch (error) {
            console.error('Failed to connect to Mercure hub:', error);
        }
    }

    handleBid(event) {
        try {
            const data = JSON.parse(event.data);
            const updateData = data.updateData || data;
            this.notifyCallbacks(updateData);
            this.updateUI(updateData);
        } catch (error) {
            console.error('Error processing Mercure data:', error);
        }
    }

    handleError(error) {
        console.error('Mercure connection error:', error);
        setTimeout(() => {
            this.reconnect();
        }, 5000);
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }

    reconnect() {
        this.disconnect();
        this.connect();
    }

    onBidUpdate(callback) {
        this.onBidUpdateCallbacks.push(callback);
    }

    notifyCallbacks(data) {
        this.onBidUpdateCallbacks.forEach(callback => callback(data));
    }

    updateUI(data) {
        const elements = {
            highestBid: document.getElementById('highest-bid'),
            minimumBid: document.getElementById('minimum-bid'),
            lastBidder: document.getElementById('last-bidder'),
            bidForm: document.getElementById('bid-form'),
            bidAmount: document.getElementById('bid_amount')
        };

        if (elements.highestBid) {
            elements.highestBid.textContent = `${parseFloat(data.highestBid).toLocaleString('fr-FR')} €`;
        }

        if (elements.minimumBid) {
            elements.minimumBid.textContent = `${parseFloat(data.minimumBid).toLocaleString('fr-FR')} €`;
        }

        if (elements.lastBidder) {
            elements.lastBidder.textContent = data.bidder;
        }

        if (elements.bidAmount) {
            elements.bidAmount.min = data.minimumBid;
            elements.bidAmount.placeholder = `Minimum ${parseFloat(data.minimumBid).toLocaleString('fr-FR')} €`;
        }

        this.showNotification(data);
    }

    async handleBidSubmit(event) {
        event.preventDefault();

        const form = event.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const amount = form.querySelector('#bid_amount').value;

        try {
            if (submitButton) {
                submitButton.disabled = true;
            }

            const response = await fetch(`/api/auction/${this.auctionId}/bid`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ amount: parseFloat(amount) })
            });

            const responseData = await response.json();

            if (!response.ok) {
                throw new Error(responseData.error || 'Une erreur est survenue');
            }

            if (responseData.updateData) {
                this.updateUI(responseData.updateData);
            }

            form.reset();
            this.showSuccessMessage('Enchère placée avec succès !');

        } catch (error) {
            console.error('Bid error:', error);
            this.showErrorMessage(error.message);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    showSuccessMessage(message) {
        const alertContainer = this.getOrCreateAlertContainer();
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.textContent = message;

        alertContainer.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }

    showErrorMessage(message) {
        const alertContainer = this.getOrCreateAlertContainer();
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger';
        alert.textContent = message;

        alertContainer.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }

    getOrCreateAlertContainer() {
        let container = document.querySelector('.alert-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'alert-container';
            const form = document.getElementById('bid-form');
            form.parentNode.insertBefore(container, form);
        }
        return container;
    }

    showNotification(data) {
        if (this.notificationsEnabled) {
            new Notification('Nouvelle enchère !', {
                body: `Nouvelle enchère de ${parseFloat(data.highestBid).toLocaleString('fr-FR')}€ par ${data.bidder}`
            });
        }
    }
}

// Style pour les alertes
const style = document.createElement('style');
style.textContent = `
    .alert-container {
        margin-bottom: 1rem;
    }

    .alert {
        padding: 1rem;
        margin-bottom: 0.5rem;
        border-radius: 0.25rem;
        animation: fadeIn 0.3s ease-in;
    }

    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(style);

// Initialisation de la classe
document.addEventListener('DOMContentLoaded', function() {
    const auctionConfig = document.getElementById('auction-config');
    if (auctionConfig) {
        try {
            window.auctionClient = new AuctionMercureClient();
            window.auctionClient.connect();
            console.log('AuctionMercureClient initialized successfully');
        } catch (error) {
            console.error('Failed to initialize AuctionMercureClient:', error);
        }
    }
});

export default AuctionMercureClient;