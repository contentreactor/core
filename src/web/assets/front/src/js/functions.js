/** 
 * @param {string} selector
 * @returns {HTMLElement}
 */
 export const q = selector => document.querySelector(selector)

/** 
* @param {string} selector
* @returns {NodeList}
*/
export const qA = selector => document.querySelectorAll(selector)

export class Node {
	constructor(type, parent = null, atts = null) {
		this.node = document.createElement(String(type).toUpperCase())
		this.nodeParent = parent instanceof Node ? parent : null
		this.parent = parent instanceof Node ? parent.node : parent
		this.atts = atts
		this.children = []
		this.siblings = this.nodeParent ? this.nodeParent.children : []
		this.nodeParent && this.nodeParent.children.push(this)
		this.nodeParent && this.parent.appendChild(this.node)
		this.renderAtts()
		return this
	}
	sibling(type, atts = null) {
		return new Node(type, this.nodeParent, atts)
	}
	child(type, atts = null) {
		var child = new Node(type, this, atts)
		this.children.push(child)
		return child
	}
	addClass(classes) {
		this.node.classList.add(classes)
	}
	removeClass(classes) {
		this.node.classList.remove(classes)
	}
	renderAtts() {
		this.atts &&
			Object.keys(this.atts).forEach(key => {
				switch (key) {
					case 'innerHTML':
						this.node.innerHTML = this.atts[key]
						break
					case 'textContent':
						this.node.textContent = replaceAllBackSlash(this.atts[key])
						break
					case 'innerText':
						var text = document.createTextNode(replaceAllBackSlash(this.atts[key]))
						this.node.appendChild(text)
						break
					case 'required':
						this.node.required = true
						break
					case 'disabled':
						this.node.disabled = true
						break
					case 'handlers':
						Object.keys(this.atts[key]).forEach(event => {
							this.node.addEventListener(event, this.atts[key][event])
						})
						break
					default:
						this.node.setAttribute(key, this.atts[key])
				}
			})
	}
	render() {
		if (this.nodeParent) {
			this.nodeParent.render()
			return
		}
		this.parent.appendChild(this.node)
	}
}

export const fadeOut = el => {
    el.style.opacity = 1
        ; (function fade() {
            if ((el.style.opacity -= 0.1) < 0) {
                el.style.display = 'none'
            } else {
                requestAnimationFrame(fade)
            }
        })()
}

export const fadeIn = (el, display) => {
    el.style.opacity = 0
    el.style.display = display || 'block'
    el.classList.remove('d-none')
        ; (function fade() {
            var val = parseFloat(el.style.opacity)
            if (!((val += 0.1) > 1)) {
                el.style.opacity = val
                requestAnimationFrame(fade)
            }
        })()
}

export const createElementFromHTML = htmlString => {
    let div = document.createElement('div')
    div.innerHTML = htmlString.trim()
    return div.firstChild
}


