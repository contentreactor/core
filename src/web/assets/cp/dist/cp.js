document.addEventListener('click', e => {
	if (!e.target.closest('.field-tab')) return
	if (e.target.classList.contains('active')) return
	let fieldTabWrapper = e.target.closest('.link-field-inner')
	e.target.closest('.field-tab-wrapper').querySelectorAll('.field-tab').forEach(el => el.classList.remove('active'))
	e.target.classList.add('active')

	fieldTabWrapper.querySelectorAll('.flex-fields').forEach(el => {
		el.classList.remove('active')
	})

	fieldTabWrapper.querySelector(`[id*="field-tab-${e.target.getAttribute('data-tab')}"]`).classList.add('active')
})