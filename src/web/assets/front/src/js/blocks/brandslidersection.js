import { q } from "../functions";
q('#form-test').addEventListener('submit', e => {
    e.preventDefault()
    let token = q('#_token')
    const body = new FormData()
    body.append(token.getAttribute('name'), token.value)
    body.append(q('#test').id,q('#test').value)

    fetch('actions/contentreactor-core/test/index', {
        method: 'POST',
        mode: 'cors',
        cache: 'no-cache',
        credentials: 'same-origin',
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: body
    })
        .then(response => response.json())
        .then(response => {
            console.log(response)
        })
        .catch(err => console.log(err))
})