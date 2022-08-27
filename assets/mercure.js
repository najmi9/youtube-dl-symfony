import { EventSourcePolyfill } from 'event-source-polyfill';

const LAST_EVENT_ID_KEY = 'lastEventId';

export default function mercure() {

    const hubUrl = new URL(document.body.dataset.url);
    const topic = document.body.dataset.topic;
    hubUrl.searchParams.append('topic', topic);

    const lastEventId = localStorage.getItem(LAST_EVENT_ID_KEY);

    if (null !== lastEventId){
        hubUrl.searchParams.append('lastEventID', lastEventId);
    }

    const subscriptionToken = document.body.dataset.token;

    const headers =  {
        headers: {
            Authorization: 'Bearer ' + subscriptionToken,
        },
    };

    const eventSource = new EventSourcePolyfill(hubUrl, headers);

    const cancelBtn = cancelButtonHandler();

    const outputDiv = document.querySelector('div#output');

    const div = document.createElement('div');
    const divContainer = document.createElement('div');
    divContainer.classList.add('border', 'border-primary', 'm-3')

    div.id = 'loader-output';

    divContainer.appendChild(div);

    outputDiv.appendChild(divContainer);

    const p = document.createElement('p');

    p.classList.add('d-flex', 'justify-content-center', 'align-items-center', 'mt-3');

    p.innerHTML = `<p class="output-line">...</p>`;

    outputDiv.appendChild(p);

    /**
     * @param {MessageEvent} e
     */
    eventSource.onmessage = (e) => {
        localStorage.setItem(LAST_EVENT_ID_KEY, e.lastEventId);

        const {processId, progressNumber} = JSON.parse(e.data);

        if (!cancelBtn.hasAttribute('data-id')) {
            cancelBtn.setAttribute('data-id', processId);
        }

        if (outputDiv.classList.contains('d-none')) {
            outputDiv.style.opacity = 1;
            outputDiv.classList.remove('d-none');
        }

        if (progressNumber && progressNumber.includes('%')) {
            div.style.width = progressNumber;
            p.innerHTML = `<p class="output-line">${progressNumber}</p>`;
        }
    };

    window.addEventListener('beforeunload', () => {
        if (eventSource != null) {
            eventSource.close();
        }
    });

    return topic;
}

function cancelButtonHandler () {
    const cancelBtn = document.querySelector('button#cancel-downloading');

    cancelBtn.addEventListener(
        'click',
        () => fetch(`/cancel-download/${cancelBtn.dataset.id}`),
        {once: true}
    );

    return cancelBtn;
}
