import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

const ROOT_ID = 'docupharma-app-guide';
const CONFIG_ID = 'docupharma-app-guide-config';
const RESTART_EVENT = 'docupharma-app-guide-restart';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function readConfig() {
    const configEl = document.getElementById(CONFIG_ID);

    if (!configEl) {
        return null;
    }

    try {
        return JSON.parse(configEl.textContent || '{}');
    } catch (error) {
        console.error('DocuPharma app guide config is invalid.', error);

        return null;
    }
}

function readBoot() {
    const root = document.getElementById(ROOT_ID);
    const config = readConfig();

    if (!root || !config) {
        return null;
    }

    return {
        root,
        autoStart: root.dataset.autoStart === '1',
        config,
    };
}

function normalizeLabel(value) {
    return (value ?? '').replace(/\s+/g, ' ').trim().toLowerCase();
}

function findSidebarGroup(groupLabel) {
    if (!groupLabel) {
        return null;
    }

    const group = document.querySelector(`.fi-sidebar-group[data-group-label="${CSS.escape(groupLabel)}"]`);

    if (group) {
        return group.querySelector('.fi-sidebar-group-btn') ?? group;
    }

    const target = normalizeLabel(groupLabel);
    const buttons = document.querySelectorAll('.fi-sidebar-group-btn');

    for (const button of buttons) {
        if (normalizeLabel(button.textContent) === target) {
            return button;
        }
    }

    return null;
}

function findSidebarItem(itemLabel) {
    if (!itemLabel) {
        return null;
    }

    const target = normalizeLabel(itemLabel);
    const items = document.querySelectorAll('.fi-sidebar-item-button, .fi-sidebar-item a, a.fi-sidebar-item-button');

    for (const item of items) {
        if (normalizeLabel(item.textContent) === target) {
            return item;
        }
    }

    return null;
}

function resolveElement(step) {
    if (step.itemLabel) {
        const item = findSidebarItem(step.itemLabel);
        if (item) {
            return item;
        }
    }

    if (step.groupLabel) {
        return findSidebarGroup(step.groupLabel);
    }

    return null;
}

async function postJson(url) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({}),
    });

    if (!response.ok) {
        throw new Error(`App guide request failed (${response.status})`);
    }

    return response.json();
}

function ensureWelcomeModal() {
    let modal = document.getElementById('docupharma-app-guide-welcome');

    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.id = 'docupharma-app-guide-welcome';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.innerHTML = `
        <div class="dp-app-guide-backdrop" data-app-guide-dismiss></div>
        <div class="dp-app-guide-panel">
            <h2 class="dp-app-guide-title">Welcome to DocuPharma</h2>
            <p class="dp-app-guide-copy">
                Take a short tour of the menus, open the Knowledge Library, or skip and explore on your own.
                You can restart this guide anytime from your user menu.
            </p>
            <div class="dp-app-guide-actions">
                <button type="button" class="dp-app-guide-btn dp-app-guide-btn-primary" data-app-guide-tour>Take the tour</button>
                <button type="button" class="dp-app-guide-btn" data-app-guide-library>Open Knowledge Library</button>
                <button type="button" class="dp-app-guide-btn dp-app-guide-btn-muted" data-app-guide-skip>Skip</button>
            </div>
        </div>
    `;

    if (!document.getElementById('docupharma-app-guide-welcome-style')) {
        const style = document.createElement('style');
        style.id = 'docupharma-app-guide-welcome-style';
        style.textContent = `
            #docupharma-app-guide-welcome {
                position: fixed;
                inset: 0;
                z-index: 10000;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
            }
            #docupharma-app-guide-welcome.is-open { display: flex; }
            .dp-app-guide-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, 0.55);
            }
            .dp-app-guide-panel {
                position: relative;
                width: min(32rem, 100%);
                border-radius: 1rem;
                background: #fff;
                color: #0f172a;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
                padding: 1.75rem;
            }
            .dp-app-guide-title {
                margin: 0 0 0.75rem;
                font-size: 1.35rem;
                font-weight: 650;
                line-height: 1.3;
            }
            .dp-app-guide-copy {
                margin: 0 0 1.25rem;
                color: #475569;
                line-height: 1.55;
                font-size: 0.95rem;
            }
            .dp-app-guide-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.6rem;
            }
            .dp-app-guide-btn {
                border: 1px solid #cbd5e1;
                background: #fff;
                color: #0f172a;
                border-radius: 0.65rem;
                padding: 0.55rem 0.9rem;
                font-size: 0.9rem;
                font-weight: 600;
                cursor: pointer;
            }
            .dp-app-guide-btn-primary {
                background: #d97706;
                border-color: #d97706;
                color: #fff;
            }
            .dp-app-guide-btn-muted {
                border-color: transparent;
                background: transparent;
                color: #64748b;
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(modal);

    return modal;
}

function createGuideApi(boot) {
    let driverObj = null;
    const { config } = boot;

    async function markComplete() {
        await postJson(config.completeUrl);
        boot.root.dataset.autoStart = '0';
    }

    function closeWelcome() {
        ensureWelcomeModal().classList.remove('is-open');
    }

    function openWelcome() {
        const modal = ensureWelcomeModal();

        modal.querySelector('[data-app-guide-tour]').onclick = () => {
            closeWelcome();
            startTour();
        };

        modal.querySelector('[data-app-guide-library]').onclick = async () => {
            try {
                await markComplete();
            } catch (error) {
                console.error(error);
            }
            closeWelcome();
            window.location.href = config.knowledgeLibraryUrl;
        };

        modal.querySelector('[data-app-guide-skip]').onclick = async () => {
            try {
                await markComplete();
            } catch (error) {
                console.error(error);
            }
            closeWelcome();
        };

        modal.querySelector('[data-app-guide-dismiss]').onclick = async () => {
            try {
                await markComplete();
            } catch (error) {
                console.error(error);
            }
            closeWelcome();
        };

        modal.classList.add('is-open');
    }

    function buildSteps() {
        const steps = Array.isArray(config.steps) ? config.steps : [];

        return steps
            .map((step) => {
                const element = resolveElement(step);
                const needsElement = Boolean(step.groupLabel || step.itemLabel);

                if (needsElement && !element) {
                    return null;
                }

                return {
                    element: element ?? undefined,
                    popover: {
                        title: step.title,
                        description: step.description,
                        side: 'right',
                        align: 'start',
                    },
                };
            })
            .filter(Boolean);
    }

    function startTour() {
        const steps = buildSteps();

        if (steps.length === 0) {
            void markComplete();

            return;
        }

        if (driverObj) {
            driverObj.destroy();
        }

        driverObj = driver({
            showProgress: true,
            animate: true,
            overlayOpacity: 0.55,
            nextBtnText: 'Next',
            prevBtnText: 'Back',
            doneBtnText: 'Open Knowledge Library',
            steps,
            onDestroyStarted: () => {
                if (!driverObj) {
                    return;
                }

                const isLast = driverObj.isLastStep?.() ?? false;

                void markComplete()
                    .catch((error) => console.error(error))
                    .finally(() => {
                        driverObj.destroy();
                        driverObj = null;

                        if (isLast) {
                            window.location.href = config.knowledgeLibraryUrl;
                        }
                    });
            },
        });

        driverObj.drive();
    }

    async function restart() {
        try {
            await postJson(config.restartUrl);
            boot.root.dataset.autoStart = '1';
            openWelcome();
        } catch (error) {
            console.error(error);
            openWelcome();
        }
    }

    function openAfterServerRestart() {
        boot.root.dataset.autoStart = '1';
        openWelcome();
    }

    return {
        openWelcome,
        startTour,
        restart,
        openAfterServerRestart,
    };
}

function bootAppGuide({ forceOpen = false } = {}) {
    const boot = readBoot();

    if (!boot) {
        return null;
    }

    const api = createGuideApi(boot);
    window.DocuPharmaAppGuide = api;

    if (forceOpen || boot.autoStart) {
        requestAnimationFrame(() => {
            setTimeout(() => api.openWelcome(), 250);
        });
    }

    return api;
}

function onRestartEvent() {
    const api = window.DocuPharmaAppGuide ?? bootAppGuide();

    if (!api) {
        console.error('DocuPharma app guide could not start. Hard-refresh the page and try again.');

        return;
    }

    api.openAfterServerRestart();
}

document.addEventListener(RESTART_EVENT, onRestartEvent);

document.addEventListener('livewire:navigated', () => {
    bootAppGuide();
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => bootAppGuide());
} else {
    bootAppGuide();
}
