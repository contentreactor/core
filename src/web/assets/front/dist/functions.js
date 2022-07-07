/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/functions.js":
/*!*****************************!*\
  !*** ./src/js/functions.js ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"q\": () => (/* binding */ q),\n/* harmony export */   \"qA\": () => (/* binding */ qA),\n/* harmony export */   \"Node\": () => (/* binding */ Node),\n/* harmony export */   \"fadeOut\": () => (/* binding */ fadeOut),\n/* harmony export */   \"fadeIn\": () => (/* binding */ fadeIn),\n/* harmony export */   \"createElementFromHTML\": () => (/* binding */ createElementFromHTML)\n/* harmony export */ });\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\n/** \n * @param {string} selector\n * @returns {HTMLElement}\n */\nvar q = function q(selector) {\n  return document.querySelector(selector);\n};\n/** \n* @param {string} selector\n* @returns {NodeList}\n*/\n\nvar qA = function qA(selector) {\n  return document.querySelectorAll(selector);\n};\nvar Node = /*#__PURE__*/function () {\n  function Node(type) {\n    var parent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;\n    var atts = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;\n\n    _classCallCheck(this, Node);\n\n    this.node = document.createElement(String(type).toUpperCase());\n    this.nodeParent = parent instanceof Node ? parent : null;\n    this.parent = parent instanceof Node ? parent.node : parent;\n    this.atts = atts;\n    this.children = [];\n    this.siblings = this.nodeParent ? this.nodeParent.children : [];\n    this.nodeParent && this.nodeParent.children.push(this);\n    this.nodeParent && this.parent.appendChild(this.node);\n    this.renderAtts();\n    return this;\n  }\n\n  _createClass(Node, [{\n    key: \"sibling\",\n    value: function sibling(type) {\n      var atts = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;\n      return new Node(type, this.nodeParent, atts);\n    }\n  }, {\n    key: \"child\",\n    value: function child(type) {\n      var atts = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;\n      var child = new Node(type, this, atts);\n      this.children.push(child);\n      return child;\n    }\n  }, {\n    key: \"addClass\",\n    value: function addClass(classes) {\n      this.node.classList.add(classes);\n    }\n  }, {\n    key: \"removeClass\",\n    value: function removeClass(classes) {\n      this.node.classList.remove(classes);\n    }\n  }, {\n    key: \"renderAtts\",\n    value: function renderAtts() {\n      var _this = this;\n\n      this.atts && Object.keys(this.atts).forEach(function (key) {\n        switch (key) {\n          case 'innerHTML':\n            _this.node.innerHTML = _this.atts[key];\n            break;\n\n          case 'textContent':\n            _this.node.textContent = replaceAllBackSlash(_this.atts[key]);\n            break;\n\n          case 'innerText':\n            var text = document.createTextNode(replaceAllBackSlash(_this.atts[key]));\n\n            _this.node.appendChild(text);\n\n            break;\n\n          case 'required':\n            _this.node.required = true;\n            break;\n\n          case 'disabled':\n            _this.node.disabled = true;\n            break;\n\n          case 'handlers':\n            Object.keys(_this.atts[key]).forEach(function (event) {\n              _this.node.addEventListener(event, _this.atts[key][event]);\n            });\n            break;\n\n          default:\n            _this.node.setAttribute(key, _this.atts[key]);\n\n        }\n      });\n    }\n  }, {\n    key: \"render\",\n    value: function render() {\n      if (this.nodeParent) {\n        this.nodeParent.render();\n        return;\n      }\n\n      this.parent.appendChild(this.node);\n    }\n  }]);\n\n  return Node;\n}();\nvar fadeOut = function fadeOut(el) {\n  el.style.opacity = 1;\n\n  (function fade() {\n    if ((el.style.opacity -= 0.1) < 0) {\n      el.style.display = 'none';\n    } else {\n      requestAnimationFrame(fade);\n    }\n  })();\n};\nvar fadeIn = function fadeIn(el, display) {\n  el.style.opacity = 0;\n  el.style.display = display || 'block';\n  el.classList.remove('d-none');\n\n  (function fade() {\n    var val = parseFloat(el.style.opacity);\n\n    if (!((val += 0.1) > 1)) {\n      el.style.opacity = val;\n      requestAnimationFrame(fade);\n    }\n  })();\n};\nvar createElementFromHTML = function createElementFromHTML(htmlString) {\n  var div = document.createElement('div');\n  div.innerHTML = htmlString.trim();\n  return div.firstChild;\n};\n\n//# sourceURL=webpack:///./src/js/functions.js?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/js/functions.js"](0, __webpack_exports__, __webpack_require__);
/******/ 	
/******/ })()
;