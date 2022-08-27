import mercure from './mercure';
import './styles/app.css';

(() => {
    const topic = mercure();

    const form = document.querySelector('form[name="url"]');
    if (null === form) {
        console.error('Can not find form form[name="url"] in the DOM');
        return;
    }

    const frameContainer = document.querySelector('.frame');

    /**
     * @param {SubmitEvent} e
     */
    form.onsubmit =async e => {
        e.preventDefault();
        const form = Object.fromEntries(new FormData(e.target));

        const url = form['url'];
        const isMp4 = 'video' === e.submitter.dataset.type;
        let urlSplitted = [];

        if (url.includes('v=')) {
            urlSplitted = url.split('v=');
        } else {
            urlSplitted = url.split('/');
        }

        const id = urlSplitted.slice(-1);

        frameContainer.innerHTML = `
            <iframe width="560" height="315" src="https://www.youtube.com/embed/${id}" title="YouTube video player"
                frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>
            </iframe>
        `;

        const res = await fetch(
            '/download',
            {
                method: 'POST',
                body: JSON.stringify({url, isMp4, topic})
            }
        );

        const json = await res.json();

        return location.href = json.url;
    }
})()
