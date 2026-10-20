// "Node" vem com o alias "TiptapNode": o nome "Node" já é a interface global
// do DOM (usada em formatHtmlCode() mais abaixo via Node.ELEMENT_NODE/
// Node.TEXT_NODE) — importar sem alias sombreava o global e fazia
// formatHtmlCode() devolver sempre string vazia (nodeType nunca igual a
// "undefined"), apagando o conteúdo do modal "Ver código".
import { Editor, Extension, Mark, Node as TiptapNode, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import Highlight from '@tiptap/extension-highlight';
import TextStyle from '@tiptap/extension-text-style';
import Color from '@tiptap/extension-color';
import Table from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TaskList from '@tiptap/extension-task-list';
import TaskItem from '@tiptap/extension-task-item';
import Superscript from '@tiptap/extension-superscript';
import Subscript from '@tiptap/extension-subscript';
import BulletList from '@tiptap/extension-bullet-list';
import OrderedList from '@tiptap/extension-ordered-list';
// "selectionCell"/"colCount" não têm comando equivalente em @tiptap/extension-table
// (usados só por sortTableColumn(), ver window.tiptapEditor() mais abaixo) —
// @tiptap/pm é o próprio prosemirror-tables reempacotado pelo Tiptap.
import { selectionCell, colCount } from '@tiptap/pm/tables';
// Plugin/PluginKey são usados pela barrinha flutuante de tabela (ver
// TableFloatingToolbar mais abaixo) — não tem equivalente via addCommands(),
// precisa de um Plugin de verdade pra reagir a toda mudança de seleção.
import { Plugin, PluginKey, NodeSelection } from '@tiptap/pm/state';

// Realce de sintaxe HTML pro modal "Ver código" (ver tiptapEditor() mais
// abaixo) — tokeniza na mão em vez de instalar um pacote (Prism/CodeMirror)
// só pra isso; cobre tag/atributo/valor/comentário, que é o que aparece
// num HTML de artigo. Devolve HTML (com <span class="tok-...">), já
// escapado — nunca interpola o código do usuário sem escapar.
function escapeHtml(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function highlightTag(tag) {
    const match = tag.match(/^<(\/?)([a-zA-Z][a-zA-Z0-9-]*)([^>]*?)(\/?)>$/);
    if (!match) return escapeHtml(tag);
    const [, slash, name, rest, selfClose] = match;

    const attrRegex = /([a-zA-Z_:][a-zA-Z0-9_:.-]*)(\s*=\s*)("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')/g;
    let attrsHtml = '';
    let lastIndex = 0;
    let m;
    while ((m = attrRegex.exec(rest))) {
        attrsHtml += escapeHtml(rest.slice(lastIndex, m.index));
        attrsHtml += `<span class="tok-attr">${escapeHtml(m[1])}</span>${escapeHtml(m[2])}<span class="tok-string">${escapeHtml(m[3])}</span>`;
        lastIndex = m.index + m[0].length;
    }
    attrsHtml += escapeHtml(rest.slice(lastIndex));

    return `<span class="tok-bracket">&lt;${slash}</span><span class="tok-tag">${escapeHtml(name)}</span>${attrsHtml}<span class="tok-bracket">${selfClose}&gt;</span>`;
}

function highlightHtml(code) {
    let out = '';
    let i = 0;
    const len = code.length;

    while (i < len) {
        if (code.startsWith('<!--', i)) {
            let end = code.indexOf('-->', i);
            end = end === -1 ? len : end + 3;
            out += `<span class="tok-comment">${escapeHtml(code.slice(i, end))}</span>`;
            i = end;
            continue;
        }

        if (code[i] === '<') {
            const end = code.indexOf('>', i);
            if (end === -1) {
                out += escapeHtml(code.slice(i));
                break;
            }
            out += highlightTag(code.slice(i, end + 1));
            i = end + 1;
            continue;
        }

        const next = code.indexOf('<', i);
        const stop = next === -1 ? len : next;
        out += escapeHtml(code.slice(i, stop));
        i = stop;
    }

    return out;
}

// Reindenta o HTML gerado (botão "Format code") refazendo a árvore via
// DOMParser e serializando com indentação — sem pacote externo. Não
// preserva espaçamento sensível de inline (ex. dentro de <pre>), mas é
// suficiente pra conferir/ajustar a estrutura do artigo.
function formatHtmlCode(html) {
    const container = document.createElement('div');
    container.innerHTML = html;
    const voidTags = ['img', 'br', 'hr', 'input', 'meta', 'link', 'source', 'col', 'area'];

    const walk = (node, depth) => {
        const pad = '  '.repeat(depth);

        if (node.nodeType === Node.TEXT_NODE) {
            const text = node.textContent.trim();
            return text ? `${pad}${text}\n` : '';
        }

        if (node.nodeType !== Node.ELEMENT_NODE) {
            return '';
        }

        const tag = node.tagName.toLowerCase();
        const attrs = [...node.attributes].map((a) => ` ${a.name}="${a.value}"`).join('');

        if (voidTags.includes(tag)) {
            return `${pad}<${tag}${attrs}>\n`;
        }

        const children = [...node.childNodes].map((child) => walk(child, depth + 1)).join('');

        if (!children) {
            return `${pad}<${tag}${attrs}></${tag}>\n`;
        }

        return `${pad}<${tag}${attrs}>\n${children}${pad}</${tag}>\n`;
    };

    return [...container.childNodes].map((child) => walk(child, 0)).join('').trim();
}

// Paleta fixa do dropdown de "Cor do texto"/"Cor de destaque" (3 linhas de
// 6: cores vivas, versão escura das mesmas cores, escala de cinza) — igual
// ao seletor de cor do Google Docs, que é a referência pedida.
const COLOR_PRESETS = [
    '#059669', '#eab308', '#f97316', '#ef4444', '#ec4899', '#3b82f6',
    '#0f766e', '#a16207', '#c2410c', '#b91c1c', '#be185d', '#1d4ed8',
    '#ffffff', '#d1d5db', '#9ca3af', '#6b7280', '#374151', '#000000',
];

// Conversões de cor usadas pelo modal "Color Picker" (quadrado de
// saturação/valor + barra de matiz + campos R/G/B/hex) — na mão, sem
// biblioteca, igual ao resto do realce/formatação deste arquivo.
function hsvToRgb(h, s, v) {
    s /= 100;
    v /= 100;
    const c = v * s;
    const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
    const m = v - c;
    let r = 0, g = 0, b = 0;
    if (h < 60) { r = c; g = x; b = 0; }
    else if (h < 120) { r = x; g = c; b = 0; }
    else if (h < 180) { r = 0; g = c; b = x; }
    else if (h < 240) { r = 0; g = x; b = c; }
    else if (h < 300) { r = x; g = 0; b = c; }
    else { r = c; g = 0; b = x; }
    return {
        r: Math.round((r + m) * 255),
        g: Math.round((g + m) * 255),
        b: Math.round((b + m) * 255),
    };
}

function rgbToHsv(r, g, b) {
    r /= 255;
    g /= 255;
    b /= 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const d = max - min;
    let h = 0;
    if (d !== 0) {
        if (max === r) h = (((g - b) / d) % 6);
        else if (max === g) h = (b - r) / d + 2;
        else h = (r - g) / d + 4;
        h *= 60;
        if (h < 0) h += 360;
    }
    const s = max === 0 ? 0 : (d / max) * 100;
    const v = max * 100;
    return { h, s, v };
}

function rgbToHex(r, g, b) {
    return '#' + [r, g, b].map((n) => Math.max(0, Math.min(255, Math.round(n))).toString(16).padStart(2, '0')).join('');
}

function hexToRgb(hex) {
    const match = hex.trim().replace('#', '').match(/^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i);
    if (!match) {
        return null;
    }
    return { r: parseInt(match[1], 16), g: parseInt(match[2], 16), b: parseInt(match[3], 16) };
}

// Transformações do menu "Aa" da toolbar (lowercase/UPPERCASE/Title Case) —
// só mexe nos caracteres, nunca no tamanho da string, então as posições no
// documento não mudam (ver transformCase() em window.tiptapEditor()).
function applyTextCase(text, mode) {
    if (mode === 'lowercase') {
        return text.toLowerCase();
    }
    if (mode === 'uppercase') {
        return text.toUpperCase();
    }
    if (mode === 'titlecase') {
        return text.replace(/\p{L}[\p{L}\p{M}']*/gu, (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase());
    }
    return text;
}

// Deduz a opção ativa do menu "Aa" a partir do texto selecionado — não é
// um estado guardado, é só uma leitura do texto atual (ver detectTextCase()
// em window.tiptapEditor()).
function detectCase(text) {
    const letters = text.replace(/[^\p{L}]/gu, '');
    if (!letters) {
        return null;
    }
    if (letters === letters.toUpperCase() && letters !== letters.toLowerCase()) {
        return 'uppercase';
    }
    if (letters === letters.toLowerCase() && letters !== letters.toUpperCase()) {
        return 'lowercase';
    }
    const isTitleCase = text.split(/\s+/).filter(Boolean).every((word) => {
        const clean = word.replace(/[^\p{L}]/gu, '');
        return !clean || (clean[0] === clean[0].toUpperCase() && clean.slice(1) === clean.slice(1).toLowerCase());
    });
    return isTitleCase ? 'titlecase' : null;
}

// Dispara o modal de confirmação (x-ui.confirm-modal, incluído uma vez nos
// layouts) em vez do confirm() nativo do navegador. Uso nos botões:
// @click="confirmAction('Confirma a exclusão?', () => $wire.delete(123))"
window.confirmAction = (message, callback) => {
    window.dispatchEvent(new CustomEvent('request-confirm', { detail: { message, callback } }));
};

// Extensão de fonte, feita à mão em cima do TextStyle (já instalado) em vez
// de instalar "@tiptap/extension-font-family" — mesma técnica do pacote
// oficial: só adiciona o atributo "fontFamily" na marca "textStyle".
const FontFamily = Extension.create({
    name: 'fontFamily',
    addOptions() {
        return { types: ['textStyle'] };
    },
    addGlobalAttributes() {
        return [{
            types: this.options.types,
            attributes: {
                fontFamily: {
                    default: null,
                    parseHTML: (element) => element.style.fontFamily,
                    renderHTML: (attributes) => {
                        if (!attributes.fontFamily) {
                            return {};
                        }

                        return { style: `font-family: ${attributes.fontFamily}` };
                    },
                },
            },
        }];
    },
    addCommands() {
        return {
            setFontFamily: (fontFamily) => ({ chain }) => chain().setMark('textStyle', { fontFamily }).run(),
        };
    },
});

// Extensão de tamanho de fonte, no mesmo molde da FontFamily acima (em
// cima do TextStyle já instalado, sem precisar do pacote oficial
// "@tiptap/extension-font-size", que nem existe separado — o próprio
// Tiptap recomenda esse padrão feito à mão).
const FontSize = Extension.create({
    name: 'fontSize',
    addOptions() {
        return { types: ['textStyle'] };
    },
    addGlobalAttributes() {
        return [{
            types: this.options.types,
            attributes: {
                fontSize: {
                    default: null,
                    parseHTML: (element) => element.style.fontSize,
                    renderHTML: (attributes) => {
                        if (!attributes.fontSize) {
                            return {};
                        }

                        return { style: `font-size: ${attributes.fontSize}` };
                    },
                },
            },
        }];
    },
    addCommands() {
        return {
            setFontSize: (fontSize) => ({ chain }) => chain().setMark('textStyle', { fontSize }).run(),
        };
    },
});

// Link com atributo "title" — a extensão oficial já tem href/target/rel,
// mas não "title" (usado no campo "Title" do modal "Insert/Edit Link").
const LinkWithTitle = Link.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            title: {
                default: null,
                parseHTML: (element) => element.getAttribute('title'),
                renderHTML: (attributes) => (attributes.title ? { title: attributes.title } : {}),
            },
        };
    },
});

// Ctrl+K abre o modal "Insert/Edit Link" (ver openLinkModal() em
// window.tiptapEditor()) — dispara um evento DOM comum (não um custom
// event do Alpine) que sobe até a div raiz do componente, onde
// "x-on:open-link-modal" o escuta; é a mesma ponte usada por
// "tiptap-image-inserted" pro upload de imagem.
const LinkShortcut = Extension.create({
    name: 'linkShortcut',
    addKeyboardShortcuts() {
        return {
            'Mod-k': () => {
                this.editor.view.dom.dispatchEvent(new CustomEvent('open-link-modal', { bubbles: true }));
                return true;
            },
        };
    },
});

// Nó genérico <div> — StarterKit só tem <p> (Paragraph) como bloco de texto
// simples; usado pelo item "Div" do submenu "Blocks" do menu de formato.
const Div = TiptapNode.create({
    name: 'div',
    group: 'block',
    content: 'inline*',
    parseHTML() {
        return [{ tag: 'div' }];
    },
    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes), 0];
    },
    addCommands() {
        return {
            setDiv: () => ({ commands }) => commands.setNode(this.name),
        };
    },
});

// Marcas usadas pelo submenu "Styles" do menu de formato — classes CSS
// simples (ver resources/css/app.css), diferente de Highlight (que já
// existe pra cor de destaque customizada e também aparece nesse submenu).
const SmallCaps = Mark.create({
    name: 'smallCaps',
    parseHTML() {
        return [{ tag: 'span.ts-small-caps' }];
    },
    renderHTML({ HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes, { class: 'ts-small-caps' }), 0];
    },
    addCommands() {
        return {
            toggleSmallCaps: () => ({ commands }) => commands.toggleMark(this.name),
        };
    },
});

const Subtitle = Mark.create({
    name: 'subtitle',
    parseHTML() {
        return [{ tag: 'span.ts-subtitle' }];
    },
    renderHTML({ HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes, { class: 'ts-subtitle' }), 0];
    },
    addCommands() {
        return {
            toggleSubtitle: () => ({ commands }) => commands.toggleMark(this.name),
        };
    },
});

// Ícones SVG (string, 16x16, stroke atual) usados na barrinha flutuante da
// imagem — mesmos paths dos botões de alinhar/etc. da toolbar principal,
// só que montados via DOM puro aqui porque o NodeView não passa por Blade.
const ICON_PATHS = {
    alignLeft: 'M3 6h18M3 12h12M3 18h16',
    alignCenter: 'M4 6h16M8 12h8M4 18h12',
    alignRight: 'M3 6h18M9 12h12M5 18h16',
    alignNone: 'M3 6h18M3 12h18M3 18h18',
    // Usados pela caixa "Drop files here" do ImagePlaceholder (ver abaixo).
    imagePlaceholder: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    uploadFile: 'M3 7a2 2 0 012-2h3.172a2 2 0 011.414.586l1.828 1.828A2 2 0 0012.828 8H19a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM12 17v-6m0 0l-2.5 2.5M12 11l2.5 2.5',
    linkUrl: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
    back: 'M15 19l-7-7 7-7',
    apply: 'M5 13l4 4L19 7',
    // Alça de "pegar e mover" a tabela inteira (TableSelectAndMove, mais
    // abaixo) — seta nas 4 direções, igual ícone de "mover" do TinyMCE.
    move: 'M12 2v8m0 0l-3-3m3 3l3-3M12 22v-8m0 0l-3 3m3-3l3 3M2 12h8m0 0l-3-3m3 3l-3 3M22 12h-8m0 0l3-3m-3 3l3 3',
    // Usados pela barrinha flutuante de tabela (TableFloatingToolbar, mais
    // abaixo) — mesmo ícone de tabela do botão da toolbar principal.
    table: 'M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5zM3 10h18M3 15h18M9 3v18',
    trash: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16',
    rowInsertBefore: 'M12 3v6M8 6l4-4 4 4M4 15h16M4 20h16',
    rowInsertAfter: 'M4 4h16M4 9h16M12 15v6m-4-3l4 4 4-4',
    rowDelete: 'M4 8h16M4 16h16M9 9l6 6M15 9l-6 6',
    colInsertBefore: 'M3 12h6M6 8l-4 4 4 4M16 3v18M21 3v18',
    colInsertAfter: 'M3 3v18M8 3v18M15 12h6m-3-4l4 4-4 4',
    colDelete: 'M8 3v18M16 3v18M10 9l4 6M14 9l-4 6',
};

function svgIcon(path, size = 14) {
    return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="${path}"></path></svg>`;
}

// Acha onde soltar um nó arrastado na mão (ver dragstart manual da tabela e
// da imagem, mais abaixo) — acha o bloco de nível mais alto sob o cursor e
// decide "antes"/"depois" dele pela metade de cima/baixo. Usado em vez do
// drag-and-drop nativo do navegador porque esse mecanismo não reordena o
// documento neste editor (confirmado via instrumentação de dragstart/
// dragover/drop: os eventos disparam certinho, mas o documento nunca
// muda). Pra imagem soltada do LADO de outra, ver resolveImageDropPos
// abaixo — "posAtCoords" (usado aqui) não é confiável pra decidir
// esquerda/direita em áreas vazias ao lado de um bloco estreito.
function resolveDropPos(view, clientX, clientY) {
    const coords = view.posAtCoords({ left: clientX, top: clientY });
    if (!coords) {
        return null;
    }
    const $pos = view.state.doc.resolve(coords.pos);
    const blockPos = $pos.depth > 0 ? $pos.before(1) : coords.pos;
    const blockNode = view.state.doc.nodeAt(blockPos);
    if (!blockNode) {
        return { pos: coords.pos, rect: null, side: 'after', targetPos: null, targetNode: null };
    }
    const blockDOM = view.nodeDOM(blockPos);
    if (!(blockDOM instanceof HTMLElement)) {
        return { pos: blockPos, rect: null, side: 'after', targetPos: null, targetNode: null };
    }

    const rect = blockDOM.getBoundingClientRect();
    const after = clientY > rect.top + rect.height / 2;
    return {
        pos: after ? blockPos + blockNode.nodeSize : blockPos,
        rect,
        side: after ? 'bottom' : 'top',
        targetPos: blockPos,
        targetNode: blockNode,
    };
}

// Acha a imagem mais próxima do cursor (por distância geométrica real até
// o retângulo de cada <img>, não por hit-testing de texto) — usado só pra
// decidir se um arraste de imagem deve soltar do LADO de outra imagem.
// "posAtCoords" (resolveDropPos acima) é ótimo pra achar posição em texto,
// mas em áreas vazias ao lado de uma imagem estreita (menor que a largura
// do editor) ele pode "pular" pro próximo bloco de forma imprevisível —
// medir a distância até as imagens de verdade é mais confiável aqui.
// Devolve null se não tiver nenhuma imagem a até 80px (quem chamou cai de
// volta pro resolveDropPos genérico, baseado em texto/parágrafo).
function resolveImageDropPos(view, draggedImgEl, clientX, clientY) {
    const candidates = [...view.dom.querySelectorAll('img')].filter((el) => el !== draggedImgEl);
    let best = null;
    let bestDist = Infinity;
    for (const el of candidates) {
        const rect = el.getBoundingClientRect();
        const dx = Math.max(rect.left - clientX, 0, clientX - rect.right);
        const dy = Math.max(rect.top - clientY, 0, clientY - rect.bottom);
        const dist = Math.hypot(dx, dy);
        if (dist < bestDist) {
            bestDist = dist;
            best = { el, rect };
        }
    }
    if (!best || bestDist > 80) {
        return null;
    }

    const pos = view.posAtDOM(best.el, 0);
    const $pos = view.state.doc.resolve(pos);
    const blockPos = $pos.depth > 0 ? $pos.before(1) : pos;
    const blockNode = view.state.doc.nodeAt(blockPos);
    if (!blockNode || blockNode.type.name !== 'image') {
        return null;
    }

    const { rect } = best;
    const distances = {
        top: Math.abs(clientY - rect.top),
        bottom: Math.abs(clientY - rect.bottom),
        left: Math.abs(clientX - rect.left),
        right: Math.abs(clientX - rect.right),
    };
    const side = Object.keys(distances).reduce((a, b) => (distances[a] <= distances[b] ? a : b));
    const pastEnd = side === 'right' || side === 'bottom';
    return {
        pos: pastEnd ? blockPos + blockNode.nodeSize : blockPos,
        rect,
        side,
        targetPos: blockPos,
        targetNode: blockNode,
    };
}

// Acha se o cursor está perto da borda esquerda/direita de um bloco de
// TEXTO (parágrafo, título, citação etc. — imagem já é tratada acima, por
// distância real até os <img>) pra soltar a imagem arrastada do lado dele,
// flutuando (ver align/float em ResizableImage) em vez de empilhada
// embaixo. Sem isso, resolveDropPos só reconhece "em cima"/"embaixo" de
// qualquer bloco — nunca "do lado" de um parágrafo que não seja imagem.
function resolveParagraphSideDropPos(view, clientX, clientY) {
    const coords = view.posAtCoords({ left: clientX, top: clientY });
    if (!coords) {
        return null;
    }
    const $pos = view.state.doc.resolve(coords.pos);
    const blockPos = $pos.depth > 0 ? $pos.before(1) : coords.pos;
    const blockNode = view.state.doc.nodeAt(blockPos);
    if (!blockNode || blockNode.type.name === 'image') {
        return null; // imagem alvo já é tratada por resolveImageDropPos
    }
    const blockDOM = view.nodeDOM(blockPos);
    if (!(blockDOM instanceof HTMLElement)) {
        return null;
    }

    // Só conta como "do lado" perto de uma das bordas (não o meio do
    // bloco) — senão qualquer ponto dentro de um parágrafo largo soltaria
    // do lado, mesmo bem no centro do texto.
    const rect = blockDOM.getBoundingClientRect();
    if (clientY < rect.top || clientY > rect.bottom) {
        return null;
    }
    const sideZone = Math.min(rect.width * 0.3, 160);
    if (clientX - rect.left < sideZone) {
        return { pos: blockPos, rect, side: 'left', targetPos: blockPos, targetNode: blockNode };
    }
    if (rect.right - clientX < sideZone) {
        return { pos: blockPos, rect, side: 'right', targetPos: blockPos, targetNode: blockNode };
    }
    return null;
}

// Move um nó (imagem ou tabela) de [from, from+node.nodeSize) pra "dropPos"
// — mesma lógica nos dois lugares, por isso extraída aqui. "attrs", se
// passado, substitui os atributos do nó movido (usado pra imagem: ajustar
// o alinhamento conforme soltou do lado ou em cima/baixo de outra).
function moveNodeTo(view, from, node, dropPos, attrs) {
    const nodeEnd = from + node.nodeSize;
    if (dropPos >= from && dropPos <= nodeEnd) {
        return false; // soltou dentro/perto do próprio lugar — não faz nada
    }
    const tr = view.state.tr;
    tr.delete(from, nodeEnd);
    const mappedDrop = tr.mapping.map(dropPos);
    const nodeToInsert = attrs ? node.type.create({ ...node.attrs, ...attrs }, node.content, node.marks) : node;
    tr.insert(mappedDrop, nodeToInsert);
    view.dispatch(tr);
    return true;
}

// Extensão de imagem com NodeView personalizado:
// – atributo "width" controlável pelos handles de redimensionar
// – atributo "align" (left/center/right/null) controlável pela barrinha
//   flutuante que aparece ao selecionar a imagem — "esquerda"/"direita"
//   usa float de verdade, pra o texto do(s) parágrafo(s) seguintes fluir
//   ao redor da imagem em vez de ficar embaixo (como legendas ao lado de
//   foto em jornal/revista); "centro" continua em bloco (própria linha)
// – atributo "caption" (legenda) — vira um <figcaption> abaixo da imagem
//   no HTML publicado
// – handles nos 4 cantos aparecem ao clicar; arraste o handle para
//   redimensionar a imagem diretamente no editor
// – draggable: true mantém o arrastar nativo do ProseMirror
const ResizableImage = Image.extend({
    draggable: true,

    addAttributes() {
        return {
            ...this.parent(),
            width: {
                default: null,
                parseHTML: (element) => element.style.width || null,
                renderHTML: () => ({}), // combinado no renderHTML() abaixo
            },
            // Independente de "width": só é definida quando o usuário usa
            // uma alça de BORDA (meio de cima/baixo, ver addNodeView) pra
            // esticar só na vertical — sempre em px (diferente de "width",
            // % não funciona pra altura sem a imagem ter um ancestral de
            // altura definida). Uma alça de CANTO zera isto de volta (volta
            // a seguir a largura proporcionalmente, pela proporção natural
            // da imagem).
            height: {
                default: null,
                parseHTML: (element) => element.style.height || null,
                renderHTML: () => ({}), // combinado no renderHTML() abaixo
            },
            align: {
                default: null,
                parseHTML: (element) => element.style.float === 'left' ? 'left'
                    : element.style.float === 'right' ? 'right'
                    : element.style.marginLeft === 'auto' && element.style.marginRight === 'auto' ? 'center'
                    : null,
                renderHTML: () => ({}), // combinado abaixo
            },
            caption: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-caption') || null,
                renderHTML: () => ({}), // combinado abaixo
            },
        };
    },

    renderHTML({ node, HTMLAttributes }) {
        // "float" pra esquerda/direita: o texto dos parágrafos seguintes
        // flui ao redor da imagem em vez de ficar embaixo — mesma lógica
        // do addNodeView() acima, aplicada aqui pro HTML publicado (fora
        // do editor) ficar igual ao que se vê editando.
        const sideBySide = node.attrs.align === 'left' || node.attrs.align === 'right';
        const styleParts = sideBySide ? [`float: ${node.attrs.align}`] : ['display: block'];
        if (node.attrs.width) styleParts.push(`width: ${node.attrs.width}`);
        if (node.attrs.height) styleParts.push(`height: ${node.attrs.height}`);
        // "clear" só nos casos NÃO flutuantes: sem isso, centralizada/sem
        // alinhamento ainda é um bloco comum, que não limpa float sozinho
        // — poderia ficar sobreposta a uma imagem flutuante anterior em
        // vez de começar limpa embaixo dela.
        if (node.attrs.align === 'center') styleParts.push('margin-left: auto', 'margin-right: auto', 'margin-bottom: 0', 'clear: both');
        else if (node.attrs.align === 'left') styleParts.push('margin-top: 0', 'margin-right: 1rem', 'margin-bottom: 0.5rem');
        else if (node.attrs.align === 'right') styleParts.push('margin-top: 0', 'margin-left: 1rem', 'margin-bottom: 0.5rem');
        else styleParts.push('margin-bottom: 0', 'clear: both');

        if (node.attrs.caption) {
            const figureAttrs = mergeAttributes(this.options.HTMLAttributes, {
                style: `${styleParts.join('; ')}; margin-top: 0;`,
                'data-caption': node.attrs.caption,
            });
            return ['figure', figureAttrs,
                ['img', mergeAttributes(HTMLAttributes, { style: 'display: block; width: 100%;' })],
                ['figcaption', { style: 'text-align: center; font-size: 0.875em; color: #64748b; margin-top: 0.375em;' }, node.attrs.caption],
            ];
        }

        const imgAttrs = mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, {
            style: styleParts.join('; '),
        });

        return ['img', imgAttrs];
    },

    addNodeView() {
        return ({ node, getPos, editor }) => {
            // outer: controla a posição/alinhamento no documento. "block"
            // (própria linha) por padrão, mas vira float (esquerda/direita)
            // quando alinhada — é isso que faz o texto do(s) parágrafo(s)
            // seguintes fluir ao redor da imagem (e também permite duas
            // imagens ficarem lado a lado). Não usamos mais "draggable"
            // nativo do HTML aqui — ver o mousedown manual no "inner", mais
            // abaixo, e o comentário ali do porquê.
            const outer = document.createElement('div');
            outer.style.cssText = 'display: block; line-height: 0; user-select: none; vertical-align: top;';

            // inner: inline-block que abraça a imagem de forma precisa.
            // Outline de seleção, handles e a barrinha flutuante são
            // ancorados aqui via position:absolute.
            const inner = document.createElement('div');
            inner.style.cssText = 'display: inline-block; position: relative; cursor: grab; line-height: 0;';

            const img = document.createElement('img');
            // margin: 0 anula o "margin-top/bottom: 2em" que o Tailwind
            // Typography (".prose img", classe do editor) aplicaria aqui —
            // sem isso a imagem fica deslocada pra baixo dentro do "inner",
            // e o contorno/handles (que abraçam o "inner") ficam bem maiores
            // que a imagem, desalinhados.
            img.style.cssText = 'display: block; max-width: 100%; margin: 0;';
            // <img> tem arrastar nativo próprio do navegador — mesmo sem
            // usarmos mais "draggable" no outer, o <img> ainda tentaria
            // iniciar o drag nativo dele por conta própria.
            img.draggable = false;

            // Legenda mostrada embaixo da imagem, só no editor (no HTML
            // publicado o renderHTML já gera o <figcaption> de verdade).
            const captionEl = document.createElement('div');
            captionEl.style.cssText = 'text-align: center; font-size: 0.8125rem; color: var(--text-muted); margin-top: 0.375rem; font-style: italic;';

            // Sincroniza atributos do nó com o DOM.
            const syncAttrs = (attrs) => {
                img.src = attrs.src || '';
                img.alt = attrs.alt || '';
                if (attrs.title) img.title = attrs.title;

                // "inner" é inline-block (abraça o conteúdo) e width:% no
                // filho cria uma referência circular — o navegador resolve
                // usando a largura disponível do bloco (editor inteiro) em
                // vez da própria imagem, fazendo o inner (e as alças, que
                // são ancoradas nele) ficarem do tamanho do editor. Por
                // isso convertemos % para px aqui só para exibição no
                // editor; o atributo (e o HTML publicado) continua em %.
                if (attrs.width && attrs.width.endsWith('%')) {
                    const pct = parseFloat(attrs.width) / 100;
                    const availableWidth = editor.view.dom.clientWidth;
                    img.style.width = availableWidth ? `${Math.round(availableWidth * pct)}px` : attrs.width;
                } else {
                    img.style.width = attrs.width || '';
                }
                // "height" é sempre px (ver addAttributes) — some (volta a
                // seguir a proporção natural da imagem) quando null.
                img.style.height = attrs.height || '';

                // Alinhamento à esquerda/direita usa float de verdade — o
                // texto do(s) parágrafo(s) seguintes flui ao redor da
                // imagem em vez de ficar embaixo (e outra imagem alinhada
                // do mesmo lado também encosta, pela própria mecânica do
                // float). "Centro"/sem alinhamento continua "block" (própria
                // linha, centralizada por margin:auto).
                if (attrs.align === 'left' || attrs.align === 'right') {
                    outer.style.display = 'block';
                    outer.style.float = attrs.align;
                    outer.style.clear = '';
                    outer.style.marginLeft = attrs.align === 'right' ? '1rem' : '';
                    outer.style.marginRight = attrs.align === 'left' ? '1rem' : '';
                    outer.style.marginBottom = '0.5rem';
                } else {
                    outer.style.display = 'block';
                    outer.style.float = 'none';
                    // Mesmo sem flutuar, ainda é um bloco comum — sem
                    // "clear" poderia ficar sobreposta a uma imagem
                    // flutuante anterior em vez de começar limpa embaixo.
                    outer.style.clear = 'both';
                    outer.style.marginLeft = attrs.align === 'center' ? 'auto' : '';
                    outer.style.marginRight = attrs.align === 'center' ? 'auto' : '';
                    outer.style.marginBottom = '';
                }

                captionEl.textContent = attrs.caption || '';
                captionEl.style.display = attrs.caption ? 'block' : 'none';
            };
            syncAttrs(node.attrs);

            inner.appendChild(img);
            inner.appendChild(captionEl);
            outer.appendChild(inner);

            // Cria um handle de redimensionamento num dos cantos do inner.
            const makeHandle = (posH, posV) => {
                const cursorMap = {
                    'left-top': 'nw-resize', 'right-top': 'ne-resize',
                    'left-bottom': 'sw-resize', 'right-bottom': 'se-resize',
                };
                const handle = document.createElement('div');
                handle.style.cssText = `
                    position: absolute;
                    ${posH}: -6px;
                    ${posV}: -6px;
                    width: 12px;
                    height: 12px;
                    background: #fff;
                    border: 2px solid var(--admin-primary, #0C4A34);
                    border-radius: 2px;
                    cursor: ${cursorMap[`${posH}-${posV}`]};
                    display: none;
                    z-index: 20;
                    box-shadow: 0 1px 3px rgba(0,0,0,.25);
                `;

                handle.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const startX     = e.clientX;
                    const startWidth = img.getBoundingClientRect().width;
                    // Handles à esquerda (NW/SW) invertem o sinal do dx.
                    const sign = posH === 'right' ? 1 : -1;

                    // Alça de canto = redimensionar PROPORCIONAL: zera a
                    // altura manual (se houver, ver alças de borda abaixo)
                    // assim que o arraste começa, pra voltar a seguir a
                    // proporção natural da imagem a partir da nova largura.
                    img.style.height = '';

                    const onMove = (mv) => {
                        const newW = Math.max(40, startWidth + sign * (mv.clientX - startX));
                        img.style.width = `${newW}px`;
                    };

                    const onUp = () => {
                        document.removeEventListener('mousemove', onMove);
                        document.removeEventListener('mouseup', onUp);

                        // Converte px final em % relativa à largura do editor.
                        const finalPx = img.getBoundingClientRect().width;
                        const editorW = editor.view.dom.clientWidth || finalPx;
                        const pct     = Math.round((finalPx / editorW) * 100);
                        const pos = typeof getPos === 'function' ? getPos() : null;
                        if (pos !== null) {
                            editor.chain()
                                .setNodeSelection(pos)
                                .updateAttributes('image', { width: `${pct}%`, height: null })
                                .run();
                        }
                    };

                    document.addEventListener('mousemove', onMove);
                    document.addEventListener('mouseup', onUp);
                });

                inner.appendChild(handle);
                return handle;
            };

            // Alça no meio de uma borda (topo/baixo = só altura, em px;
            // esquerda/direita = só largura, em %, igual às de canto) —
            // permite esticar num eixo só, deixando a imagem desproporcional
            // de propósito (ex.: logo largo e baixo, foto bem vertical).
            const makeEdgeHandle = (side) => {
                const vertical = side === 'top' || side === 'bottom';
                const styleBySide = {
                    top: 'top: -6px; left: 50%; transform: translateX(-50%); cursor: n-resize;',
                    bottom: 'bottom: -6px; left: 50%; transform: translateX(-50%); cursor: s-resize;',
                    left: 'left: -6px; top: 50%; transform: translateY(-50%); cursor: w-resize;',
                    right: 'right: -6px; top: 50%; transform: translateY(-50%); cursor: e-resize;',
                };
                const handle = document.createElement('div');
                handle.style.cssText = `
                    position: absolute;
                    ${styleBySide[side]}
                    width: 12px;
                    height: 12px;
                    background: #fff;
                    border: 2px solid var(--admin-primary, #0C4A34);
                    border-radius: 2px;
                    display: none;
                    z-index: 20;
                    box-shadow: 0 1px 3px rgba(0,0,0,.25);
                `;

                handle.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const startX = e.clientX;
                    const startY = e.clientY;
                    const startRect = img.getBoundingClientRect();
                    // Alças de cima/esquerda invertem o sinal do delta.
                    const sign = (side === 'bottom' || side === 'right') ? 1 : -1;

                    const onMove = (mv) => {
                        if (vertical) {
                            const newH = Math.max(20, startRect.height + sign * (mv.clientY - startY));
                            img.style.height = `${newH}px`;
                        } else {
                            const newW = Math.max(40, startRect.width + sign * (mv.clientX - startX));
                            img.style.width = `${newW}px`;
                        }
                    };

                    const onUp = () => {
                        document.removeEventListener('mousemove', onMove);
                        document.removeEventListener('mouseup', onUp);

                        const pos = typeof getPos === 'function' ? getPos() : null;
                        if (pos === null) return;

                        if (vertical) {
                            const finalPx = Math.round(img.getBoundingClientRect().height);
                            editor.chain().setNodeSelection(pos).updateAttributes('image', { height: `${finalPx}px` }).run();
                        } else {
                            const finalPx = img.getBoundingClientRect().width;
                            const editorW = editor.view.dom.clientWidth || finalPx;
                            const pct     = Math.round((finalPx / editorW) * 100);
                            editor.chain().setNodeSelection(pos).updateAttributes('image', { width: `${pct}%` }).run();
                        }
                    };

                    document.addEventListener('mousemove', onMove);
                    document.addEventListener('mouseup', onUp);
                });

                inner.appendChild(handle);
                return handle;
            };

            // 8 handles: 4 cantos (NW/NE/SW/SE, proporcional) + 4 bordas
            // (topo/baixo/esquerda/direita, só um eixo por vez).
            const handles = [
                makeHandle('left',  'top'),
                makeHandle('right', 'top'),
                makeHandle('left',  'bottom'),
                makeHandle('right', 'bottom'),
                makeEdgeHandle('top'),
                makeEdgeHandle('bottom'),
                makeEdgeHandle('left'),
                makeEdgeHandle('right'),
            ];

            // Barrinha flutuante (estilo TinyMCE): aparece embaixo da
            // imagem quando selecionada, com alinhar/legenda/ALT. Roda os
            // comandos direto no editor (não passa pelos métodos do Alpine
            // porque este NodeView não tem acesso ao x-data do componente).
            const toolbar = document.createElement('div');
            toolbar.style.cssText = `
                position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
                margin-top: 8px; display: none; z-index: 20;
                background: var(--bg-surface, #fff); border: 1px solid var(--border-color, #e2e8f0);
                border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.15);
                padding: 4px; gap: 2px; align-items: center;
            `;
            toolbar.style.display = 'none';

            const runOnThisImage = (fn) => {
                const pos = typeof getPos === 'function' ? getPos() : null;
                if (pos === null) return;
                editor.chain().setNodeSelection(pos).run();
                fn(editor);
            };

            const toolbarButton = (html, title, onClick, active = false) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.title = title;
                btn.innerHTML = html;
                btn.style.cssText = `
                    display: inline-flex; align-items: center; justify-content: center;
                    padding: 6px 8px; border-radius: 6px; cursor: pointer; border: none;
                    background: ${active ? 'var(--bg-hover, #f1f5f9)' : 'transparent'};
                    color: var(--text-secondary, #475569); font-size: 11px; font-weight: 700;
                `;
                btn.addEventListener('mouseenter', () => { btn.style.background = 'var(--bg-hover, #f1f5f9)'; });
                btn.addEventListener('mouseleave', () => { btn.style.background = active ? 'var(--bg-hover, #f1f5f9)' : 'transparent'; });
                // stopPropagation além do preventDefault — sem isso o
                // mousedown "escapa" pro listener de arrastar do "inner"
                // (mais abaixo) e clicar num botão da barrinha dispara o
                // início de um arraste da imagem por engano.
                btn.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); });
                btn.addEventListener('click', (e) => { e.stopPropagation(); onClick(); });
                toolbar.appendChild(btn);
                return btn;
            };

            // Sem botão de ALT/Legenda de propósito: eram os dois últimos
            // da barrinha e, em imagens menores/mais perto da próxima,
            // faziam a barrinha cobrir o conteúdo logo abaixo. Quem
            // precisar de ALT/legenda ainda pode editar via "Ver código".
            const rebuildToolbar = (attrs) => {
                toolbar.innerHTML = '';
                toolbarButton(svgIcon(ICON_PATHS.alignLeft), 'Alinhar à esquerda', () => runOnThisImage((e) => e.chain().updateAttributes('image', { align: 'left' }).run()), attrs.align === 'left');
                toolbarButton(svgIcon(ICON_PATHS.alignCenter), 'Centralizar', () => runOnThisImage((e) => e.chain().updateAttributes('image', { align: 'center' }).run()), attrs.align === 'center');
                toolbarButton(svgIcon(ICON_PATHS.alignRight), 'Alinhar à direita', () => runOnThisImage((e) => e.chain().updateAttributes('image', { align: 'right' }).run()), attrs.align === 'right');
                toolbarButton(svgIcon(ICON_PATHS.alignNone), 'Remover alinhamento', () => runOnThisImage((e) => e.chain().updateAttributes('image', { align: null }).run()), !attrs.align);
            };
            rebuildToolbar(node.attrs);
            inner.appendChild(toolbar);

            const showHandles = () => handles.forEach(h => { h.style.display = 'block'; });
            const hideHandles = () => handles.forEach(h => { h.style.display = 'none'; });

            // Arrastar pra reposicionar a imagem — calculado na mão (mesma
            // técnica da tabela, ver TableFloatingToolbar), não com o
            // drag-and-drop nativo do navegador: confirmado via
            // instrumentação que esse mecanismo dispara dragstart/dragover/
            // drop certinho, mas nunca reordena o documento neste editor.
            // Fica no "inner" (não no "outer"): handles e botões da
            // barrinha já fazem stopPropagation no mousedown deles, então
            // um mousedown que chega aqui é sempre fora dessas áreas.
            let dropIndicator = null;
            inner.addEventListener('mousedown', (event) => {
                // Só previne o comportamento padrão quando a imagem JÁ está
                // selecionada: nesse caso o navegador assume que é um
                // "arrastar o conteúdo selecionado" nativo do contenteditable
                // — isso TOMA os mousemove/mouseup seguintes pra si (nenhum
                // dos dois chega no document, nem com addEventListener
                // capturing), fazendo o nosso arraste manual (onMove/onUp
                // abaixo) nunca rodar. Em QUALQUER outro caso (imagem ainda
                // não selecionada) NÃO se pode prevenir aqui — é o próprio
                // ProseMirror, no mousedown que ainda vai borbulhar pra
                // view.dom depois deste handler, que cria a NodeSelection no
                // clique simples; com preventDefault incondicional (como
                // estava antes) ele nunca cria, e clicar na imagem não
                // selecionava nada.
                const pos = typeof getPos === 'function' ? getPos() : null;
                const { selection } = editor.state;
                const alreadySelected = pos !== null && selection instanceof NodeSelection && selection.from === pos;
                if (alreadySelected) {
                    event.preventDefault();
                }
                const startX = event.clientX;
                const startY = event.clientY;
                let didMove = false;

                const onMove = (moveEvent) => {
                    if (!didMove && (Math.abs(moveEvent.clientX - startX) > 4 || Math.abs(moveEvent.clientY - startY) > 4)) {
                        didMove = true;
                        hideHandles();
                        toolbar.style.display = 'none';
                        inner.style.cursor = 'grabbing';
                        if (!dropIndicator) {
                            dropIndicator = document.createElement('div');
                            dropIndicator.style.cssText = `
                                position: absolute; z-index: 30;
                                background: var(--admin-primary, #0C4A34); border-radius: 2px; pointer-events: none;
                            `;
                            document.body.appendChild(dropIndicator);
                        }
                    }
                    if (!didMove) {
                        return;
                    }
                    const drop = resolveImageDropPos(editor.view, img, moveEvent.clientX, moveEvent.clientY)
                        || resolveParagraphSideDropPos(editor.view, moveEvent.clientX, moveEvent.clientY)
                        || resolveDropPos(editor.view, moveEvent.clientX, moveEvent.clientY);
                    if (!drop || !drop.rect) {
                        dropIndicator.style.display = 'none';
                        return;
                    }
                    dropIndicator.style.display = 'block';
                    if (drop.side === 'left' || drop.side === 'right') {
                        // Barra VERTICAL encostada na borda esquerda/direita da
                        // imagem alvo — sinaliza "vai ficar do lado", não embaixo.
                        dropIndicator.style.width = '3px';
                        dropIndicator.style.height = `${drop.rect.height}px`;
                        dropIndicator.style.left = `${(drop.side === 'left' ? drop.rect.left : drop.rect.right) + window.scrollX - 1}px`;
                        dropIndicator.style.top = `${drop.rect.top + window.scrollY}px`;
                    } else {
                        // Barra horizontal (empilhar em cima/embaixo), igual tabela.
                        dropIndicator.style.width = `${drop.rect.width}px`;
                        dropIndicator.style.height = '3px';
                        dropIndicator.style.left = `${drop.rect.left + window.scrollX}px`;
                        dropIndicator.style.top = `${(drop.side === 'bottom' ? drop.rect.bottom : drop.rect.top) + window.scrollY - 1}px`;
                    }
                };

                const onUp = (upEvent) => {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                    inner.style.cursor = 'grab';
                    if (dropIndicator) {
                        dropIndicator.style.display = 'none';
                    }
                    if (!didMove) {
                        return; // só clique: seleciona a imagem normalmente, sem mover
                    }

                    const pos = typeof getPos === 'function' ? getPos() : null;
                    if (pos === null) {
                        return;
                    }
                    const imgNode = editor.state.doc.nodeAt(pos);
                    if (!imgNode) {
                        return;
                    }
                    const drop = resolveImageDropPos(editor.view, img, upEvent.clientX, upEvent.clientY)
                        || resolveParagraphSideDropPos(editor.view, upEvent.clientX, upEvent.clientY)
                        || resolveDropPos(editor.view, upEvent.clientX, upEvent.clientY);
                    if (!drop) {
                        return;
                    }

                    if (drop.side === 'left' || drop.side === 'right') {
                        // Soltando do lado de OUTRA IMAGEM, as duas sempre
                        // flutuam "left": é a ordem no documento (ver
                        // moveNodeTo) que decide quem fica mais à esquerda
                        // ou direita, já que floats do mesmo lado se
                        // empilham na ordem em que aparecem no HTML.
                        // Soltando do lado de um PARÁGRAFO (não imagem), o
                        // lado escolhido é o alinhamento de verdade — é a
                        // própria imagem que flutua esquerda/direita em
                        // relação ao texto.
                        const isImageTarget = drop.targetNode?.type?.name === 'image';
                        const align = isImageTarget ? 'left' : drop.side;
                        if (isImageTarget && drop.targetNode.attrs.align !== 'left' && drop.targetNode.attrs.align !== 'right') {
                            editor.view.dispatch(editor.view.state.tr.setNodeMarkup(drop.targetPos, undefined, { ...drop.targetNode.attrs, align }));
                        }
                        const moved = moveNodeTo(editor.view, pos, imgNode, drop.pos, { align });
                        if (!moved && imgNode.attrs.align !== align) {
                            // Já estava adjacente (nenhuma posição pra mudar) —
                            // só falta alinhar ela também, pra caber do lado.
                            const freshPos = typeof getPos === 'function' ? getPos() : pos;
                            editor.view.dispatch(editor.view.state.tr.setNodeMarkup(freshPos, undefined, { ...imgNode.attrs, align }));
                        }
                    } else {
                        // Soltou em cima/embaixo: volta pro modo empilhado (bloco),
                        // mesmo que já estivesse alinhada à esquerda/direita antes.
                        const moved = moveNodeTo(editor.view, pos, imgNode, drop.pos, { align: null });
                        if (!moved && imgNode.attrs.align) {
                            const freshPos = typeof getPos === 'function' ? getPos() : pos;
                            editor.view.dispatch(editor.view.state.tr.setNodeMarkup(freshPos, undefined, { ...imgNode.attrs, align: null }));
                        }
                    }
                };

                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });

            return {
                dom: outer,
                contentDOM: null,

                update(updatedNode) {
                    if (updatedNode.type.name !== 'image') return false;
                    syncAttrs(updatedNode.attrs);
                    rebuildToolbar(updatedNode.attrs);
                    return true;
                },

                selectNode() {
                    showHandles();
                    toolbar.style.display = 'flex';
                    // Outline no inner — abraça só a imagem
                    inner.style.outline       = '2px solid var(--admin-primary, #0C4A34)';
                    inner.style.outlineOffset = '2px';
                    inner.style.cursor        = 'grabbing';
                },

                deselectNode() {
                    hideHandles();
                    toolbar.style.display = 'none';
                    inner.style.outline       = '';
                    inner.style.outlineOffset = '';
                    inner.style.cursor        = 'grab';
                },

                destroy() {
                    if (dropIndicator) {
                        dropIndicator.remove();
                    }
                },
            };
        };
    },
});

// Listas com atributo "listStyleType" — usado pelos menus de estilo dos
// botões de lista (setBulletListStyle()/setOrderedListStyle() em
// window.tiptapEditor()), igual ao "List Styles" do TinyMCE.
const BulletListWithStyle = BulletList.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            listStyleType: {
                default: null,
                parseHTML: (element) => element.style.listStyleType || null,
                renderHTML: (attributes) => (attributes.listStyleType ? { style: `list-style-type: ${attributes.listStyleType}` } : {}),
            },
        };
    },
});

const OrderedListWithStyle = OrderedList.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            listStyleType: {
                default: null,
                parseHTML: (element) => element.style.listStyleType || null,
                renderHTML: (attributes) => (attributes.listStyleType ? { style: `list-style-type: ${attributes.listStyleType}` } : {}),
            },
        };
    },
});

// Tabela com atributos "width"/"align" — usados pelo item "Table properties"
// do menu de tabela (openTablePropertiesModal() em window.tiptapEditor()).
// "draggable: true" deixa arrastar a tabela inteira pra outro lugar do
// documento (igual TinyMCE) — StarterKit não ativa isso por padrão.
const TableWithStyle = Table.extend({
    draggable: true,

    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: null,
                parseHTML: (element) => element.style.width || null,
                renderHTML: () => ({}), // combinado no renderHTML() abaixo
            },
            align: {
                default: null,
                parseHTML: (element) => (element.style.marginLeft === 'auto' && element.style.marginRight === 'auto'
                    ? 'center'
                    : element.style.marginRight === 'auto' ? 'left'
                    : element.style.marginLeft === 'auto' ? 'right'
                    : null),
                renderHTML: () => ({}), // combinado abaixo
            },
        };
    },
    // Reaproveita o renderHTML original (que monta o <colgroup> usado pelo
    // "resizable: true" das colunas) e só adiciona width/align por cima —
    // substituir a função inteira perderia o colgroup e quebraria o
    // redimensionamento de coluna já existente.
    renderHTML({ node, HTMLAttributes }) {
        const base = this.parent({ node, HTMLAttributes });
        const tableArray = this.options.renderWrapper ? base[2] : base;

        const styleParts = [];
        if (node.attrs.width) {
            styleParts.push(`width: ${node.attrs.width}`);
        }
        if (node.attrs.align === 'center') {
            styleParts.push('margin-left: auto', 'margin-right: auto');
        } else if (node.attrs.align === 'left') {
            styleParts.push('margin-right: auto');
        } else if (node.attrs.align === 'right') {
            styleParts.push('margin-left: auto');
        }

        if (styleParts.length) {
            const existingStyle = tableArray[1].style || '';
            tableArray[1] = {
                ...tableArray[1],
                style: `${existingStyle}${existingStyle ? '; ' : ''}${styleParts.join('; ')}`,
            };
        }

        return base;
    },
});

// Barrinha flutuante que aparece ao clicar numa célula de tabela (igual
// Tiptap/TinyMCE): tabela, excluir tabela, inserir/excluir linha, inserir/
// excluir coluna. Diferente da barrinha da imagem (que é um NodeView), uma
// tabela não tem um único "nó raiz" que a gente controle com addNodeView()
// — por isso aqui é um Plugin de verdade, que acompanha toda mudança de
// seleção (view.update) pra decidir se o cursor está dentro de uma tabela
// e, se estiver, posiciona a barrinha (um <div> fixo, fora do fluxo do
// documento) encostada no topo da tabela.
// Linha de tabela com atributo "height" — @tiptap/extension-table-row não
// tem isso por padrão (só a largura da coluna é redimensionável de
// fábrica, via columnResizing do prosemirror-tables). Usado pelo
// TableRowResizing abaixo, que é o equivalente vertical escrito à mão.
const TableRowWithHeight = TableRow.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            height: {
                default: null,
                parseHTML: (element) => {
                    const height = element.style.height;
                    return height ? parseInt(height, 10) : null;
                },
                renderHTML: (attributes) => (attributes.height ? { style: `height: ${attributes.height}px` } : {}),
            },
        };
    },
});

// Redimensionar a ALTURA da linha arrastando a borda de baixo — o
// "resizable: true" do TableWithStyle só cobre LARGURA de coluna
// (columnResizing do prosemirror-tables, horizontal); altura de linha não
// tem equivalente pronto, por isso é um Plugin próprio: mousemove no
// editor mostra uma alça horizontal quando o cursor está perto da borda
// de baixo de uma linha, e o arraste grava "height" nessa linha (tr), sem
// afetar a largura de nenhuma coluna — move num eixo só por vez, igual
// TinyMCE.
const TableRowResizing = Extension.create({
    name: 'tableRowResizing',
    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('tableRowResizing'),
                view(view) {
                    const handle = document.createElement('div');
                    handle.className = 'table-row-resize-handle';
                    handle.style.cssText = 'position: absolute; height: 6px; cursor: row-resize; z-index: 25; display: none;';
                    document.body.appendChild(handle);

                    let activeRow = null;
                    let dragging = false;

                    const rowAtPoint = (clientX, clientY) => {
                        const el = document.elementFromPoint(clientX, clientY);
                        const cell = el?.closest('td, th');
                        if (!cell || !view.dom.contains(cell)) {
                            return null;
                        }
                        return cell.closest('tr');
                    };

                    const positionHandle = (row) => {
                        const rect = row.getBoundingClientRect();
                        handle.style.display = 'block';
                        handle.style.top = `${rect.bottom + window.scrollY - 3}px`;
                        handle.style.left = `${rect.left + window.scrollX}px`;
                        handle.style.width = `${rect.width}px`;
                    };

                    const onMouseMove = (event) => {
                        if (dragging) {
                            return;
                        }
                        const row = rowAtPoint(event.clientX, event.clientY);
                        if (!row) {
                            handle.style.display = 'none';
                            activeRow = null;
                            return;
                        }
                        const rect = row.getBoundingClientRect();
                        if (Math.abs(event.clientY - rect.bottom) > 6) {
                            handle.style.display = 'none';
                            activeRow = null;
                            return;
                        }
                        activeRow = row;
                        positionHandle(row);
                    };

                    view.dom.addEventListener('mousemove', onMouseMove);

                    handle.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        if (!activeRow) {
                            return;
                        }

                        // Resolve a POSIÇÃO da linha no documento uma única vez,
                        // antes do arraste começar — não guardamos a referência
                        // DOM (como fazemos pra coluna/imagem) porque o próprio
                        // "resizable: true" (columnResizing do prosemirror-tables)
                        // também escuta mousemove sobre a tabela e, ao atualizar
                        // a decoração da alça de coluna, troca os <tr>/<td> por
                        // elementos novos — a referência antiga ficaria "órfã"
                        // (desconectada do documento) no meio do próprio arraste.
                        // Posição no documento não tem esse problema: ela some só
                        // se a estrutura do documento mudar, o que não acontece
                        // aqui até o dispatch final.
                        const startRect = activeRow.getBoundingClientRect();
                        const coords = view.posAtCoords({
                            left: startRect.left + 5,
                            top: startRect.top + startRect.height / 2,
                        });
                        if (!coords) {
                            return;
                        }
                        const $start = view.state.doc.resolve(coords.pos);
                        let rowDepth = null;
                        for (let d = $start.depth; d > 0; d--) {
                            if ($start.node(d).type.spec.tableRole === 'row') {
                                rowDepth = d;
                                break;
                            }
                        }
                        if (rowDepth === null) {
                            return;
                        }
                        const rowPos = $start.before(rowDepth);

                        dragging = true;
                        const startY = event.clientY;
                        const startHeight = startRect.height;

                        const onMove = (moveEvent) => {
                            const newHeight = Math.max(24, Math.round(startHeight + (moveEvent.clientY - startY)));
                            const currentDom = view.nodeDOM(rowPos);
                            if (currentDom instanceof HTMLElement) {
                                currentDom.style.height = `${newHeight}px`;
                                positionHandle(currentDom);
                            }
                        };

                        const onUp = (upEvent) => {
                            document.removeEventListener('mousemove', onMove);
                            document.removeEventListener('mouseup', onUp);
                            dragging = false;

                            const finalHeight = Math.max(24, Math.round(startHeight + (upEvent.clientY - startY)));
                            const rowNode = view.state.doc.nodeAt(rowPos);
                            if (rowNode) {
                                const tr = view.state.tr.setNodeMarkup(rowPos, undefined, { ...rowNode.attrs, height: finalHeight });
                                view.dispatch(tr);
                            }
                        };

                        document.addEventListener('mousemove', onMove);
                        document.addEventListener('mouseup', onUp);
                    });

                    return {
                        destroy() {
                            view.dom.removeEventListener('mousemove', onMouseMove);
                            handle.remove();
                        },
                    };
                },
            }),
        ];
    },
});

const tableFloatingToolbarKey = new PluginKey('tableFloatingToolbar');

const TableFloatingToolbar = Extension.create({
    name: 'tableFloatingToolbar',
    addProseMirrorPlugins() {
        const editor = this.editor;

        return [
            new Plugin({
                key: tableFloatingToolbarKey,
                view(view) {
                    const toolbar = document.createElement('div');
                    toolbar.style.cssText = `
                        position: absolute; display: none; z-index: 30;
                        align-items: center; gap: 2px;
                        background: var(--bg-surface, #fff); border: 1px solid var(--border-color, #e2e8f0);
                        border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.15); padding: 4px;
                    `;
                    document.body.appendChild(toolbar);

                    const button = (path, title, onClick) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.title = title;
                        btn.innerHTML = svgIcon(path, 16);
                        btn.style.cssText = `
                            display: inline-flex; align-items: center; justify-content: center;
                            width: 28px; height: 28px; border-radius: 6px; cursor: pointer; border: none;
                            background: transparent; color: var(--text-secondary, #475569);
                        `;
                        btn.addEventListener('mouseenter', () => { btn.style.background = 'var(--bg-hover, #f1f5f9)'; });
                        btn.addEventListener('mouseleave', () => { btn.style.background = 'transparent'; });
                        btn.addEventListener('mousedown', (e) => e.preventDefault());
                        btn.addEventListener('click', (e) => { e.stopPropagation(); onClick(); });
                        toolbar.appendChild(btn);
                        return btn;
                    };

                    const separator = () => {
                        const sep = document.createElement('div');
                        sep.style.cssText = 'width: 1px; height: 20px; background: var(--border-color, #e2e8f0); margin: 0 2px;';
                        toolbar.appendChild(sep);
                    };

                    button(ICON_PATHS.table, 'Propriedades da tabela', () => {
                        editor.view.dom.dispatchEvent(new CustomEvent('open-table-properties', { bubbles: true }));
                    });
                    button(ICON_PATHS.trash, 'Excluir tabela', () => editor.chain().focus().deleteTable().run());
                    separator();
                    button(ICON_PATHS.rowInsertBefore, 'Inserir linha antes', () => editor.chain().focus().addRowBefore().run());
                    button(ICON_PATHS.rowInsertAfter, 'Inserir linha depois', () => editor.chain().focus().addRowAfter().run());
                    button(ICON_PATHS.rowDelete, 'Excluir linha', () => editor.chain().focus().deleteRow().run());
                    separator();
                    button(ICON_PATHS.colInsertBefore, 'Inserir coluna antes', () => editor.chain().focus().addColumnBefore().run());
                    button(ICON_PATHS.colInsertAfter, 'Inserir coluna depois', () => editor.chain().focus().addColumnAfter().run());
                    button(ICON_PATHS.colDelete, 'Excluir coluna', () => editor.chain().focus().deleteColumn().run());

                    // Alça de "pegar e mover" (canto superior esquerdo, fora da
                    // tabela) — clicar seleciona a tabela inteira (NodeSelection,
                    // mostra o contorno azul via ".ProseMirror-selectednode") e
                    // arrastar move a tabela pra outro lugar do documento. Não
                    // usa o drag-and-drop nativo do navegador (dragstart/drop):
                    // esse mecanismo não reordena o documento neste editor (ver
                    // o mesmo problema com imagem, investigado antes) — em vez
                    // disso é um arraste calculado na mão, igual o redimensionar
                    // de linha/coluna, que já funciona de verdade.
                    const grabHandle = document.createElement('div');
                    grabHandle.title = 'Mover tabela';
                    grabHandle.innerHTML = svgIcon(ICON_PATHS.move, 14);
                    grabHandle.style.cssText = `
                        position: absolute; display: none; z-index: 30;
                        width: 22px; height: 22px; align-items: center; justify-content: center;
                        border-radius: 6px; cursor: grab;
                        background: var(--bg-surface, #fff); border: 1px solid var(--border-color, #e2e8f0);
                        color: var(--text-secondary, #475569); box-shadow: 0 2px 6px rgba(0,0,0,.15);
                    `;
                    document.body.appendChild(grabHandle);

                    const dropIndicator = document.createElement('div');
                    dropIndicator.style.cssText = `
                        position: absolute; display: none; z-index: 30; height: 3px;
                        background: var(--admin-primary, #0C4A34); border-radius: 2px; pointer-events: none;
                    `;
                    document.body.appendChild(dropIndicator);

                    // Resolve a posição de tabela/NodeSelection atual — serve
                    // tanto pro cursor comum dentro de uma célula quanto pra
                    // quando a tabela inteira já está selecionada (NodeSelection),
                    // caso em que "$from" aponta pra ANTES da tabela, não pra
                    // dentro dela.
                    const findTableStart = (state) => {
                        const { selection } = state;
                        if (selection instanceof NodeSelection && selection.node.type.spec.tableRole === 'table') {
                            return selection.from;
                        }
                        const { $from } = selection;
                        for (let d = $from.depth; d > 0; d--) {
                            if ($from.node(d).type.spec.tableRole === 'table') {
                                return $from.before(d);
                            }
                        }
                        return null;
                    };

                    grabHandle.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        const tableStart = findTableStart(view.state);
                        if (tableStart === null) {
                            return;
                        }
                        const tableNode = view.state.doc.nodeAt(tableStart);
                        if (!tableNode) {
                            return;
                        }

                        view.dispatch(view.state.tr.setSelection(NodeSelection.create(view.state.doc, tableStart)));
                        grabHandle.style.cursor = 'grabbing';

                        // Só move de verdade se o mouse realmente se deslocou —
                        // um clique simples (mousedown+mouseup no mesmo lugar)
                        // deve só selecionar a tabela (contorno acima), não
                        // arrastar ela pra perto de si mesma.
                        const startX = event.clientX;
                        const startY = event.clientY;
                        let didMove = false;

                        const onMove = (moveEvent) => {
                            if (!didMove && (Math.abs(moveEvent.clientX - startX) > 4 || Math.abs(moveEvent.clientY - startY) > 4)) {
                                didMove = true;
                            }
                            if (!didMove) {
                                return;
                            }
                            const drop = resolveDropPos(view, moveEvent.clientX, moveEvent.clientY);
                            if (!drop || !drop.rect) {
                                dropIndicator.style.display = 'none';
                                return;
                            }
                            dropIndicator.style.display = 'block';
                            dropIndicator.style.left = `${drop.rect.left + window.scrollX}px`;
                            dropIndicator.style.width = `${drop.rect.width}px`;
                            dropIndicator.style.top = `${(drop.after ? drop.rect.bottom : drop.rect.top) + window.scrollY - 1}px`;
                        };

                        const onUp = (upEvent) => {
                            document.removeEventListener('mousemove', onMove);
                            document.removeEventListener('mouseup', onUp);
                            dropIndicator.style.display = 'none';
                            grabHandle.style.cursor = 'grab';

                            if (!didMove) {
                                return; // só clique: a tabela fica selecionada, sem mover
                            }

                            const drop = resolveDropPos(view, upEvent.clientX, upEvent.clientY);
                            if (!drop) {
                                return;
                            }
                            moveNodeTo(view, tableStart, tableNode, drop.pos);
                        };

                        document.addEventListener('mousemove', onMove);
                        document.addEventListener('mouseup', onUp);
                    });

                    const updatePosition = (currentView) => {
                        // "hasFocus()" evita a barrinha aparecer sozinha ao abrir
                        // uma matéria cuja primeira coisa no corpo é uma tabela:
                        // a seleção padrão do ProseMirror ao carregar o conteúdo
                        // (sem clique nenhum do usuário ainda) fica no início do
                        // documento, que resolve "dentro" dessa tabela mesmo sem
                        // o editor estar focado/clicado.
                        const tableStart = currentView.hasFocus() ? findTableStart(currentView.state) : null;
                        if (tableStart === null) {
                            toolbar.style.display = 'none';
                            grabHandle.style.display = 'none';
                            return;
                        }
                        const dom = currentView.nodeDOM(tableStart);
                        if (!(dom instanceof HTMLElement)) {
                            toolbar.style.display = 'none';
                            grabHandle.style.display = 'none';
                            return;
                        }
                        const rect = dom.getBoundingClientRect();
                        toolbar.style.display = 'flex';
                        toolbar.style.top = `${rect.top + window.scrollY - toolbar.offsetHeight - 8}px`;
                        toolbar.style.left = `${rect.left + window.scrollX}px`;

                        grabHandle.style.display = 'flex';
                        grabHandle.style.top = `${rect.top + window.scrollY - 11}px`;
                        grabHandle.style.left = `${rect.left + window.scrollX - 11}px`;
                    };

                    updatePosition(view);

                    // A barrinha/alça são posicionadas em "px" fixos (coordenadas
                    // do documento), calculados só quando o ProseMirror dispara
                    // uma transação (ex. digitar). Rolar a área do editor (que
                    // tem scroll próprio) sozinho NÃO dispara transação nenhuma —
                    // sem isto a barrinha ficava "congelada" na posição antiga,
                    // flutuando sobre qualquer conteúdo que ficasse embaixo dela
                    // depois da rolagem. "capture: true" no window pega o evento
                    // de scroll de QUALQUER ancestral com overflow, não só da
                    // janela (eventos de scroll não "sobem"/bubble, mas passam
                    // pela fase de captura de qualquer um que esteja escutando).
                    const onScroll = () => updatePosition(view);
                    window.addEventListener('scroll', onScroll, true);

                    // "blur" não dispara transação nenhuma (só muda o foco) —
                    // sem isto, clicar fora do editor (ex. noutro campo do
                    // formulário) deixava a barrinha presa visível na tela.
                    const onBlur = () => updatePosition(view);
                    editor.on('blur', onBlur);

                    return {
                        update(updatedView) {
                            updatePosition(updatedView);
                        },
                        destroy() {
                            window.removeEventListener('scroll', onScroll, true);
                            editor.off('blur', onBlur);
                            toolbar.remove();
                            grabHandle.remove();
                            dropIndicator.remove();
                        },
                    };
                },
            }),
        ];
    },
});

// Nó temporário inserido pelo botão "Inserir imagem" da toolbar — uma caixa
// tracejada "Drop files here" com uma barrinha flutuante (enviar arquivo /
// usar URL). Nunca fica salvo de verdade: assim que o upload termina (ou a
// URL é aplicada), vira um nó "image" de verdade no mesmo lugar — ver
// resolvePlaceholderToImage() em window.tiptapEditor(). Os três eventos
// customizados (image-placeholder-pick/-file/-url) sobem até a div raiz do
// componente porque este NodeView é DOM puro, sem acesso ao x-data do Alpine
// (mesmo motivo da barrinha flutuante da imagem, acima).
const ImagePlaceholder = TiptapNode.create({
    name: 'imagePlaceholder',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: false,

    parseHTML() {
        return [{ tag: 'div[data-image-placeholder]' }];
    },
    renderHTML() {
        return ['div', { 'data-image-placeholder': 'true' }];
    },

    addNodeView() {
        return ({ editor, getPos }) => {
            const dispatch = (type, detail) => {
                editor.view.dom.dispatchEvent(new CustomEvent(type, { bubbles: true, detail }));
            };
            const thisPos = () => (typeof getPos === 'function' ? getPos() : null);

            const outer = document.createElement('div');
            outer.style.cssText = 'display: block; position: relative; margin: 0.5rem 0;';

            const box = document.createElement('div');
            box.style.cssText = `
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                gap: 0.5rem; height: 8rem; border-radius: 8px; cursor: default;
                border: 2px dashed var(--border-color, #cbd5e1); color: var(--text-muted, #94a3b8);
            `;
            box.innerHTML = `
                ${svgIcon(ICON_PATHS.imagePlaceholder, 22)}
                <span style="font-size: 0.8125rem; font-family: ui-monospace, monospace;">Drop files here</span>
            `;

            const setDragOver = (on) => {
                box.style.borderColor = on ? 'var(--admin-primary, #0C4A34)' : 'var(--border-color, #cbd5e1)';
                box.style.backgroundColor = on ? 'var(--bg-hover, #f1f5f9)' : '';
            };
            box.addEventListener('dragover', (e) => { e.preventDefault(); setDragOver(true); });
            box.addEventListener('dragleave', () => setDragOver(false));
            box.addEventListener('drop', (e) => {
                e.preventDefault();
                setDragOver(false);
                const file = e.dataTransfer?.files?.[0];
                const pos = thisPos();
                if (file && pos !== null) {
                    dispatch('image-placeholder-file', { pos, file });
                }
            });

            // Barrinha flutuante com 2 modos: padrão (upload / link) e
            // "Upload from URL" (voltar / campo / aplicar) — ver TinyMCE/
            // Tiptap "Insert image" como referência de design.
            const toolbar = document.createElement('div');
            toolbar.style.cssText = `
                position: absolute; top: -44px; left: 50%; transform: translateX(-50%);
                display: flex; align-items: center; gap: 2px; z-index: 20;
                background: var(--bg-surface, #fff); border: 1px solid var(--border-color, #e2e8f0);
                border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.15); padding: 4px;
            `;

            const iconButton = (html, title, onClick) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.title = title;
                btn.innerHTML = html;
                btn.style.cssText = `
                    display: inline-flex; align-items: center; justify-content: center;
                    width: 28px; height: 28px; border-radius: 6px; cursor: pointer; border: none;
                    background: transparent; color: var(--text-secondary, #475569);
                `;
                btn.addEventListener('mouseenter', () => { btn.style.background = 'var(--bg-hover, #f1f5f9)'; });
                btn.addEventListener('mouseleave', () => { btn.style.background = 'transparent'; });
                btn.addEventListener('mousedown', (e) => e.preventDefault());
                btn.addEventListener('click', (e) => { e.stopPropagation(); onClick(); });
                return btn;
            };

            const renderDefaultMode = () => {
                toolbar.innerHTML = '';
                toolbar.appendChild(iconButton(svgIcon(ICON_PATHS.uploadFile), 'Upload file', () => {
                    const pos = thisPos();
                    if (pos !== null) dispatch('image-placeholder-pick', { pos });
                }));
                toolbar.appendChild(iconButton(svgIcon(ICON_PATHS.linkUrl), 'Upload from URL', renderUrlMode));
            };

            const renderUrlMode = () => {
                toolbar.innerHTML = '';
                toolbar.appendChild(iconButton(svgIcon(ICON_PATHS.back), 'Back', renderDefaultMode));

                const label = document.createElement('span');
                label.textContent = 'Upload from URL:';
                label.style.cssText = 'font-size: 0.8125rem; color: var(--text-secondary, #475569); padding: 0 4px; white-space: nowrap;';
                toolbar.appendChild(label);

                const input = document.createElement('input');
                input.type = 'text';
                input.placeholder = 'https://';
                input.style.cssText = `
                    width: 220px; font-size: 0.8125rem; padding: 4px 8px; border-radius: 6px;
                    border: 1px solid var(--admin-primary, #0C4A34); background: transparent;
                    color: var(--text-primary, #0f172a); outline: none;
                `;
                input.addEventListener('mousedown', (e) => e.stopPropagation());
                input.addEventListener('keydown', (e) => {
                    e.stopPropagation();
                    if (e.key === 'Enter') { e.preventDefault(); apply(); }
                });
                toolbar.appendChild(input);

                const apply = () => {
                    const url = input.value.trim();
                    const pos = thisPos();
                    if (url && pos !== null) dispatch('image-placeholder-url', { pos, url });
                };
                toolbar.appendChild(iconButton(svgIcon(ICON_PATHS.apply), 'Apply', apply));

                input.focus();
            };

            renderDefaultMode();
            outer.appendChild(toolbar);
            outer.appendChild(box);

            return {
                dom: outer,
                contentDOM: null,
                stopEvent: () => true,
                ignoreMutation: () => true,
            };
        };
    },
});

// Guarda as instâncias reais do Tiptap FORA do objeto x-data (indexadas pelo
// elemento DOM do editor). Alpine torna reativa (via Proxy, mesma técnica do
// Vue) qualquer coisa guardada direto em "this" — e o Editor/View/State do
// ProseMirror é uma árvore de objetos mutáveis internamente, não feita pra
// ser observada por um Proxy reativo. Deixar "this.editor" apontar pro
// Editor de verdade fazia o Alpine ler um snapshot desatualizado do estado
// a cada clique, causando "Applying a mismatched transaction" em TODO
// comando da barra, mesmo com um único editor de verdade montado.
const tiptapInstances = new WeakMap();

// Fábrica do editor Tiptap (x-ui.editor.tiptap). Precisa viver aqui, não num
// bloco @script do Livewire, porque "import" de pacote nu (ex.: '@tiptap/core')
// só resolve quando o arquivo passa pelo Vite — dentro de @script o navegador
// executa o código sem bundler e o import falha silenciosamente.
window.tiptapEditor = (config) => {
    return {
        content: config.content || '',
        debounceTimer: null,

        init() {
            // Guarda contra inicialização duplicada: Alpine roda x-init duas
            // vezes neste componente na carga inicial da página (observado via
            // instrumentação), e sem isso o segundo new Editor() montaria um
            // segundo editor por cima do mesmo elemento, deixando dois
            // parágrafos vazios (e dois placeholders) na tela.
            if (tiptapInstances.has(this.$refs.editor)) {
                return;
            }

            const editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit.configure({
                        bulletList: false,
                        orderedList: false,
                    }),
                    BulletListWithStyle,
                    OrderedListWithStyle,
                    ResizableImage.configure({
                        allowBase64: true,
                    }),
                    ImagePlaceholder,
                    LinkWithTitle.configure({
                        openOnClick: false,
                    }),
                    LinkShortcut,
                    Placeholder.configure({
                        placeholder: config.placeholder,
                    }),
                    TextAlign.configure({
                        types: ['heading', 'paragraph'],
                    }),
                    Underline,
                    TextStyle,
                    Color,
                    FontFamily,
                    FontSize,
                    Highlight.configure({
                        multicolor: true,
                    }),
                    TableWithStyle.configure({
                        resizable: true,
                        // Padrão do prosemirror-tables é 5px — fácil de errar
                        // e acabar arrastando a borda errada depois que uma
                        // coluna já mudou de largura (a borda nova "andou").
                        handleWidth: 10,
                        // Padrão é false: o próprio pacote desfaz sozinho
                        // qualquer NodeSelection na tabela inteira (ela só
                        // deixa selecionar célula/linha/coluna por dentro).
                        // Sem isto, a alça de "mover tabela" não consegue
                        // deixar a tabela selecionada — a seleção volta pra
                        // onde estava um instante depois.
                        allowTableNodeSelection: true,
                    }),
                    TableRowWithHeight,
                    TableCell,
                    TableHeader,
                    TableFloatingToolbar,
                    TableRowResizing,
                    TaskList,
                    TaskItem.configure({
                        nested: true,
                    }),
                    Superscript,
                    Subscript,
                    Div,
                    SmallCaps,
                    Subtitle,
                ],
                content: this.content,
                onUpdate: ({ editor }) => {
                    this.content = editor.getHTML();
                    // Debounced: sem isso, cada tecla dispararia uma requisição
                    // Livewire (via $wire.set no x-on:content-change).
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(() => {
                        this.$dispatch('content-change', this.content);
                    }, 400);
                },
                // Dispara em TODA transação (digitar, mover o cursor, trocar
                // seleção...) — só incrementa "tick" (propriedade reativa do
                // Alpine) pra forçar isActive()/can()/fontSizeValue() a
                // reavaliar. Sem isso, os botões (negrito/itálico ativo, o
                // número no stepper de fonte) nunca atualizavam depois do
                // primeiro render, porque eles leem direto da instância real
                // do Tiptap (fora do Proxy do Alpine) e o Alpine só reavalia
                // uma expressão quando algo QUE ELA LÊ é reativo.
                onTransaction: () => {
                    this.tick++;
                },
            });

            tiptapInstances.set(this.$refs.editor, editor);
        },

        tick: 0,

        // Menu "Formato" (Parágrafo ▾): um dropdown com 5 categorias
        // (Headings/Inline/Blocks/Align/Styles), cada uma abrindo um
        // submenu lateral ao passar o mouse — ver tiptap.blade.php.
        formatMenuOpen: false,
        formatSubmenu: null,

        toggleFormatMenu() {
            this.formatMenuOpen = !this.formatMenuOpen;
            if (!this.formatMenuOpen) {
                this.formatSubmenu = null;
            }
        },

        closeFormatMenu() {
            this.formatMenuOpen = false;
            this.formatSubmenu = null;
        },

        openSubmenu(name) {
            this.formatSubmenu = name;
        },

        // Rótulo do botão do menu "Formato" — mostra o bloco atual
        // (Heading 1..6, Blockquote, Pre, Div ou Paragraph).
        currentBlockLabel() {
            this.tick; // eslint-disable-line no-unused-expressions
            const editor = tiptapInstances.get(this.$refs.editor);
            if (!editor) {
                return 'Parágrafo';
            }
            for (let level = 1; level <= 6; level++) {
                if (editor.isActive('heading', { level })) {
                    return `Título ${level}`;
                }
            }
            if (editor.isActive('blockquote')) {
                return 'Citação';
            }
            if (editor.isActive('codeBlock')) {
                return 'Bloco de código';
            }
            if (editor.isActive('div')) {
                return 'Div';
            }
            return 'Parágrafo';
        },

        // Dropdown de "Cor do texto" / "Cor de destaque" (paleta de presets +
        // remover cor + abrir o Color Picker) e o modal "Color Picker"
        // (quadrado de saturação/valor + barra de matiz + R/G/B/hex) — igual
        // ao seletor de cor do Google Docs, que foi a referência pedida.
        colorPresets: COLOR_PRESETS,
        colorMenuOpen: null, // 'text' | 'highlight' | null
        colorModalOpen: false,
        colorModalTarget: 'text', // 'text' | 'highlight'
        colorModalDragging: null, // 'square' | 'hue' | null
        colorModalHue: 0,
        colorModalSat: 0,
        colorModalVal: 0,
        colorModalR: 0,
        colorModalG: 0,
        colorModalB: 0,
        colorModalHex: '#000000',

        toggleColorMenu(which) {
            this.colorMenuOpen = this.colorMenuOpen === which ? null : which;
        },

        closeColorMenu() {
            this.colorMenuOpen = null;
        },

        applyPresetColor(which, hex) {
            if (which === 'text') {
                this.cmd((editor) => editor.chain().setColor(hex).run());
            } else {
                this.cmd((editor) => editor.chain().setHighlight({ color: hex }).run());
            }
            this.closeColorMenu();
        },

        removeColor(which) {
            if (which === 'text') {
                this.cmd((editor) => editor.chain().unsetColor().run());
            } else {
                this.cmd((editor) => editor.chain().unsetHighlight().run());
            }
            this.closeColorMenu();
        },

        openColorModal(which) {
            this.colorModalTarget = which;
            const editor = tiptapInstances.get(this.$refs.editor);
            const attrs = editor ? editor.getAttributes(which === 'text' ? 'textStyle' : 'highlight') : {};
            this.colorModalHex = attrs.color || (which === 'text' ? '#000000' : '#fef08a');
            this.syncColorModalFromHex();
            this.colorModalOpen = true;
            this.colorMenuOpen = null;
        },

        closeColorModal() {
            this.colorModalOpen = false;
            this.colorModalDragging = null;
        },

        saveColorModal() {
            if (this.colorModalTarget === 'text') {
                this.cmd((editor) => editor.chain().setColor(this.colorModalHex).run());
            } else {
                this.cmd((editor) => editor.chain().setHighlight({ color: this.colorModalHex }).run());
            }
            this.closeColorModal();
        },

        syncColorModalFromHsv() {
            const { r, g, b } = hsvToRgb(this.colorModalHue, this.colorModalSat, this.colorModalVal);
            this.colorModalR = r;
            this.colorModalG = g;
            this.colorModalB = b;
            this.colorModalHex = rgbToHex(r, g, b);
        },

        syncColorModalFromRgb() {
            const { h, s, v } = rgbToHsv(this.colorModalR, this.colorModalG, this.colorModalB);
            this.colorModalHue = h;
            this.colorModalSat = s;
            this.colorModalVal = v;
            this.colorModalHex = rgbToHex(this.colorModalR, this.colorModalG, this.colorModalB);
        },

        syncColorModalFromHex() {
            const rgb = hexToRgb(this.colorModalHex);
            if (!rgb) {
                return;
            }
            this.colorModalR = rgb.r;
            this.colorModalG = rgb.g;
            this.colorModalB = rgb.b;
            const { h, s, v } = rgbToHsv(rgb.r, rgb.g, rgb.b);
            this.colorModalHue = h;
            this.colorModalSat = s;
            this.colorModalVal = v;
        },

        updateSquareFromEvent(event) {
            const rect = this.$refs.colorSquare.getBoundingClientRect();
            const x = Math.min(Math.max(event.clientX - rect.left, 0), rect.width);
            const y = Math.min(Math.max(event.clientY - rect.top, 0), rect.height);
            this.colorModalSat = (x / rect.width) * 100;
            this.colorModalVal = 100 - (y / rect.height) * 100;
            this.syncColorModalFromHsv();
        },

        updateHueFromEvent(event) {
            const rect = this.$refs.colorHueSlider.getBoundingClientRect();
            const y = Math.min(Math.max(event.clientY - rect.top, 0), rect.height);
            this.colorModalHue = (y / rect.height) * 360;
            this.syncColorModalFromHsv();
        },

        // Menu "Aa" (lowercase / UPPERCASE / Title Case) — transforma o texto
        // selecionado na própria seleção, preservando negrito/itálico/etc.
        // (troca só o conteúdo de cada nó de texto, não o nó em si).
        caseMenuOpen: false,

        toggleCaseMenu() {
            this.caseMenuOpen = !this.caseMenuOpen;
        },

        closeCaseMenu() {
            this.caseMenuOpen = false;
        },

        transformCase(mode) {
            const editor = tiptapInstances.get(this.$refs.editor);
            if (!editor) {
                return;
            }
            const { from, to } = editor.state.selection;
            if (from === to) {
                this.closeCaseMenu();
                return;
            }
            const tr = editor.state.tr;
            editor.state.doc.nodesBetween(from, to, (node, pos) => {
                if (!node.isText) {
                    return;
                }
                const start = Math.max(from, pos);
                const end = Math.min(to, pos + node.text.length);
                if (start >= end) {
                    return;
                }
                const slice = node.text.slice(start - pos, end - pos);
                tr.insertText(applyTextCase(slice, mode), start, end);
            });
            editor.view.dispatch(tr);
            this.closeCaseMenu();
        },

        // Lê a caixa do texto selecionado pra marcar a opção ativa no menu
        // "Aa" — não é um estado salvo, é uma dedução a partir do texto.
        detectTextCase() {
            this.tick; // eslint-disable-line no-unused-expressions
            const editor = tiptapInstances.get(this.$refs.editor);
            if (!editor) {
                return null;
            }
            const { from, to } = editor.state.selection;
            if (from === to) {
                return null;
            }
            return detectCase(editor.state.doc.textBetween(from, to, ' '));
        },

        // Executa um comando no editor de verdade (fora do Proxy reativo).
        // Uso na toolbar: @click="cmd(e => e.chain().toggleBold().run())"
        cmd(run) {
            const editor = tiptapInstances.get(this.$refs.editor);
            if (editor) {
                editor.chain().focus().run();
                run(editor);
            }
        },

        isActive(name, attrs) {
            this.tick; // eslint-disable-line no-unused-expressions
            const editor = tiptapInstances.get(this.$refs.editor);
            return editor ? editor.isActive(name, attrs) : false;
        },

        can(action) {
            this.tick; // eslint-disable-line no-unused-expressions
            const editor = tiptapInstances.get(this.$refs.editor);
            return editor ? editor.can()[action]() : false;
        },

        // Modal "Insert/Edit Link" (abas General/Upload) — a aba Upload
        // reaproveita o wire:model do botão de imagem da toolbar (ver
        // "linkModalUploadPending" no listener "tiptap-image-inserted" do
        // componente, no tiptap.blade.php, e o comentário no topo do arquivo).
        linkModalOpen: false,
        linkModalTab: 'general', // 'general' | 'upload'
        linkModalUrl: '',
        linkModalText: '',
        linkModalTitle: '',
        linkModalTarget: '_self', // '_self' | '_blank'
        linkModalUploadPending: false,

        openLinkModal() {
            const editor = tiptapInstances.get(this.$refs.editor);
            if (!editor) {
                return;
            }
            const { from, to, empty } = editor.state.selection;
            const attrs = editor.getAttributes('link');
            this.linkModalUrl = attrs.href || '';
            this.linkModalTitle = attrs.title || '';
            this.linkModalTarget = attrs.target === '_blank' ? '_blank' : '_self';
            this.linkModalText = empty ? '' : editor.state.doc.textBetween(from, to, ' ');
            this.linkModalTab = 'general';
            this.linkModalOpen = true;
        },

        closeLinkModal() {
            this.linkModalOpen = false;
            this.linkModalUploadPending = false;
        },

        saveLinkModal() {
            const url = this.linkModalUrl.trim();
            if (!url) {
                this.closeLinkModal();
                return;
            }
            const linkAttrs = {
                href: url,
                target: this.linkModalTarget === '_blank' ? '_blank' : null,
                title: this.linkModalTitle.trim() || null,
            };
            const displayText = this.linkModalText.trim();
            this.cmd((editor) => {
                const { from, to, empty } = editor.state.selection;
                if (empty) {
                    editor.chain().insertContent({
                        type: 'text',
                        text: displayText || url,
                        marks: [{ type: 'link', attrs: linkAttrs }],
                    }).run();
                    return;
                }
                const currentText = editor.state.doc.textBetween(from, to, ' ');
                if (displayText && displayText !== currentText) {
                    editor.chain().insertContentAt({ from, to }, {
                        type: 'text',
                        text: displayText,
                        marks: [{ type: 'link', attrs: linkAttrs }],
                    }).run();
                    return;
                }
                editor.chain().extendMarkRange('link').setLink(linkAttrs).run();
            });
            this.closeLinkModal();
        },

        // Insere no texto a imagem cujo upload o Livewire acabou de
        // terminar (ver updatedBodyImage() no componente admin). Vem como
        // URL de storage, não base64 — base64 inflava o payload do
        // Livewire (que reenvia o corpo inteiro a cada edição) acima do
        // limite de 1MB em imagens de tamanho normal.
        insertImageUrl(url) {
            // Entra em 50% por padrão (não "muito grande" no artigo); o
            // redimensionamento pelos handles deixa trocar depois.
            this.cmd((editor) => {
                editor.chain().setImage({ src: url, width: '50%' }).run();

                // Depois do setImage a seleção fica no próprio nó da
                // imagem (NodeSelection) — encadear um insertContent direto
                // SUBSTITUIRIA essa seleção (ou seja, a imagem recém-criada
                // seria apagada). Por isso isso aqui é um comando separado,
                // com posição explícita, e só roda se não houver NADA
                // depois da imagem (ela ficou no fim do documento) — sem
                // isso não existe nenhuma posição de texto válida pra
                // clicar/continuar depois dela.
                const after = editor.state.selection.to;
                const hasNodeAfter = editor.state.doc.resolve(after).nodeAfter;
                if (!hasNodeAfter) {
                    editor.chain().insertContentAt(after, { type: 'paragraph' }).run();
                }
            });
        },

        // Botão "Inserir imagem" da toolbar: insere a caixa "Drop files
        // here" (ImagePlaceholder) em vez de abrir o seletor de arquivo
        // direto — igual ao Tiptap/TinyMCE, dá pra escolher arquivo OU
        // colar uma URL na própria barrinha flutuante da caixa.
        insertImagePlaceholder() {
            this.cmd((editor) => {
                editor.chain().insertContent({ type: 'imagePlaceholder' }).run();
            });
        },

        // Posição (no documento) do ImagePlaceholder esperando o resultado
        // de um upload disparado por ele mesmo (botão "Upload file" ou
        // arquivo arrastado direto na caixa) — ver handleImageInserted().
        pendingPlaceholderPos: null,

        // Troca o ImagePlaceholder na posição "pos" por uma imagem de
        // verdade — mesmo cuidado do insertImageUrl() com não deixar a
        // imagem sem nenhuma posição de texto válida depois dela.
        resolvePlaceholderToImage(pos, url) {
            const editor = tiptapInstances.get(this.$refs.editor);
            if (!editor) {
                return;
            }
            const node = editor.state.doc.nodeAt(pos);
            if (!node || node.type.name !== 'imagePlaceholder') {
                return;
            }
            editor.chain().focus().command(({ tr }) => {
                tr.replaceWith(pos, pos + node.nodeSize, editor.schema.nodes.image.create({ src: url, width: '50%' }));
                return true;
            }).run();
            const after = pos + 1;
            if (!editor.state.doc.resolve(after).nodeAfter) {
                editor.chain().insertContentAt(after, { type: 'paragraph' }).run();
            }
        },

        // Único handler do evento "tiptap-image-inserted" (disparado pelo
        // Livewire quando o upload termina — ver updated{uploadModel}() no
        // componente host) — decide o que fazer com a URL de acordo com
        // quem pediu o upload: a aba "Upload" do modal de link, um
        // ImagePlaceholder pendente, ou (fallback) inserir direto no cursor.
        handleImageInserted(url) {
            if (this.linkModalUploadPending) {
                this.linkModalUrl = url;
                this.linkModalUploadPending = false;
                this.linkModalTab = 'general';
                return;
            }
            if (this.pendingPlaceholderPos !== null) {
                this.resolvePlaceholderToImage(this.pendingPlaceholderPos, url);
                this.pendingPlaceholderPos = null;
                return;
            }
            this.insertImageUrl(url);
        },

        // Arquivo solto direto na caixa "Drop files here" (evento
        // "image-placeholder-file", disparado pelo NodeView do
        // ImagePlaceholder): injeta o arquivo (via DataTransfer, já que não
        // dá pra copiar um FileList de um input pra outro diretamente) no
        // input real ligado ao wire:model e dispara "change" nele — o
        // Livewire sobe o arquivo exatamente como se tivesse sido escolhido
        // ali, sem precisar de nenhuma propriedade/método novo no host.
        receiveDroppedFile(pos, file) {
            this.pendingPlaceholderPos = pos;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.$refs.imageInput.files = transfer.files;
            this.$refs.imageInput.dispatchEvent(new Event('change', { bubbles: true }));
        },

        // Alinhar/legenda/ALT da imagem agora moram na barrinha flutuante
        // que aparece ao selecionar a imagem (ver addNodeView() acima) —
        // ela chama o editor direto, não passa por aqui.

        // Menu "Table" da toolbar — grade pra escolher tamanho, e os
        // submenus Cell/Row/Column/Sort + Table properties/Delete table.
        tableMenuOpen: false,
        tableSubmenu: null, // 'table' | 'cell' | 'row' | 'column' | 'sort' | null
        gridHoverRows: 0,
        gridHoverCols: 0,

        toggleTableMenu() {
            this.tableMenuOpen = !this.tableMenuOpen;
            if (!this.tableMenuOpen) {
                this.tableSubmenu = null;
            }
        },

        closeTableMenu() {
            this.tableMenuOpen = false;
            this.tableSubmenu = null;
            this.gridHoverRows = 0;
            this.gridHoverCols = 0;
        },

        // Menu "Alinhamento" (Left/Center/Right/Justify) — igual TinyMCE.
        alignMenuOpen: false,

        toggleAlignMenu() {
            this.alignMenuOpen = !this.alignMenuOpen;
        },

        closeAlignMenu() {
            this.alignMenuOpen = false;
        },

        openTableSubmenu(name) {
            this.tableSubmenu = name;
        },

        insertTableGrid(rows, cols) {
            this.cmd((editor) => editor.chain().insertTable({ rows, cols, withHeaderRow: true }).run());
            this.closeTableMenu();
        },

        // Habilita/desabilita Cell/Row/Column/Sort/Table properties/Delete
        // table no menu — "Table" (inserir nova) fica sempre habilitado.
        insideTable() {
            this.tick; // eslint-disable-line no-unused-expressions
            const editor = tiptapInstances.get(this.$refs.editor);
            return editor ? editor.isActive('table') : false;
        },

        // Ordena as linhas da tabela pela coluna onde o cursor está — a
        // primeira linha só é tratada como cabeçalho (e fica fixa no topo)
        // se todas as células dela forem <th>. Sem comando equivalente em
        // @tiptap/extension-table, por isso usa prosemirror-tables direto
        // (selectionCell/colCount) pra achar a coluna e refaz a tabela com
        // as linhas na nova ordem.
        sortTableColumn(direction) {
            const editor = tiptapInstances.get(this.$refs.editor);
            if (!editor || !editor.isActive('table')) {
                return;
            }
            this.cmd((ed) => {
                let $cell;
                try {
                    $cell = selectionCell(ed.state);
                } catch {
                    return;
                }
                const col = colCount($cell);
                const table = $cell.node(-1);
                const tablePos = $cell.before(-1);

                const rows = [];
                table.forEach((rowNode) => rows.push(rowNode));

                const isHeaderRow = (rowNode) => {
                    let allHeader = rowNode.childCount > 0;
                    rowNode.forEach((cell) => {
                        if (cell.type.name !== 'tableHeader') {
                            allHeader = false;
                        }
                    });
                    return allHeader;
                };

                const headerRows = rows.filter(isHeaderRow);
                const bodyRows = rows.filter((row) => !isHeaderRow(row));

                const textOf = (rowNode) => {
                    const cell = rowNode.maybeChild(col);
                    return cell ? cell.textContent.trim() : '';
                };

                bodyRows.sort((a, b) => {
                    const textA = textOf(a);
                    const textB = textOf(b);
                    const numA = parseFloat(textA.replace(',', '.'));
                    const numB = parseFloat(textB.replace(',', '.'));
                    const cmp = (!Number.isNaN(numA) && !Number.isNaN(numB))
                        ? numA - numB
                        : textA.localeCompare(textB, 'pt-BR', { numeric: true, sensitivity: 'base' });
                    return direction === 'desc' ? -cmp : cmp;
                });

                const newTable = table.type.create(table.attrs, [...headerRows, ...bodyRows]);
                ed.view.dispatch(ed.state.tr.replaceWith(tablePos, tablePos + table.nodeSize, newTable));
            });
            this.closeTableMenu();
        },

        // Modal "Table properties" (largura + alinhamento) — ver TableWithStyle
        // em app.js para os atributos "width"/"align" da própria tabela.
        tablePropertiesOpen: false,
        tablePropertiesWidth: '',
        tablePropertiesAlign: '',

        openTablePropertiesModal() {
            const editor = tiptapInstances.get(this.$refs.editor);
            if (!editor || !editor.isActive('table')) {
                return;
            }
            const attrs = editor.getAttributes('table');
            this.tablePropertiesWidth = attrs.width || '';
            this.tablePropertiesAlign = attrs.align || '';
            this.tablePropertiesOpen = true;
            this.closeTableMenu();
        },

        closeTablePropertiesModal() {
            this.tablePropertiesOpen = false;
        },

        saveTableProperties() {
            this.cmd((editor) => editor.chain().updateAttributes('table', {
                width: this.tablePropertiesWidth.trim() || null,
                align: this.tablePropertiesAlign || null,
            }).run());
            this.closeTablePropertiesModal();
        },

        toggleTaskList() {
            this.cmd((editor) => editor.chain().toggleTaskList().run());
        },

        // Menus "List Styles" dos botões de lista (igual TinyMCE): o ícone
        // continua ligando/desligando a lista com um clique, a setinha ao
        // lado abre a grade de estilos (list-style-type via BulletListWithStyle/
        // OrderedListWithStyle, ver app.js).
        bulletListMenuOpen: false,
        orderedListMenuOpen: false,

        toggleBulletListMenu() {
            this.bulletListMenuOpen = !this.bulletListMenuOpen;
        },

        closeBulletListMenu() {
            this.bulletListMenuOpen = false;
        },

        toggleOrderedListMenu() {
            this.orderedListMenuOpen = !this.orderedListMenuOpen;
        },

        closeOrderedListMenu() {
            this.orderedListMenuOpen = false;
        },

        setBulletListStyle(listStyleType) {
            this.cmd((editor) => {
                if (!editor.isActive('bulletList')) {
                    editor.chain().toggleBulletList().run();
                }
                editor.chain().updateAttributes('bulletList', { listStyleType }).run();
            });
            this.closeBulletListMenu();
        },

        setOrderedListStyle(listStyleType) {
            this.cmd((editor) => {
                if (!editor.isActive('orderedList')) {
                    editor.chain().toggleOrderedList().run();
                }
                editor.chain().updateAttributes('orderedList', { listStyleType }).run();
            });
            this.closeOrderedListMenu();
        },

        // Remove toda formatação (marcas de texto + títulos/listas/citação
        // etc., voltando tudo a parágrafo simples) da seleção atual.
        clearFormatting() {
            this.cmd((editor) => editor.chain().unsetAllMarks().clearNodes().run());
        },

        fontSizeValue() {
            this.tick; // eslint-disable-line no-unused-expressions
            const editor = tiptapInstances.get(this.$refs.editor);
            const size = editor?.getAttributes('textStyle')?.fontSize;
            return size ? parseInt(size, 10) : 16;
        },

        setFontSize(px) {
            const size = Math.min(72, Math.max(8, px));
            this.cmd((editor) => editor.chain().setFontSize(`${size}px`).run());
        },

        // Modal "Ver código" (igual TinyMCE): abre com o HTML atual, deixa
        // editar com realce de sintaxe + números de linha, e só aplica de
        // volta no editor WYSIWYG se clicar "Save code" — "Cancel" descarta
        // o que foi digitado ali.
        codeViewOpen: false,
        codeViewContent: '',
        codeViewFullscreen: false,
        codeFontSize: 13,

        openCodeView() {
            const editor = tiptapInstances.get(this.$refs.editor);
            if (!editor) return;
            this.codeViewContent = formatHtmlCode(editor.getHTML());
            this.codeViewOpen = true;
        },

        cancelCodeView() {
            this.codeViewOpen = false;
        },

        saveCodeView() {
            const editor = tiptapInstances.get(this.$refs.editor);
            if (editor) {
                editor.commands.setContent(this.codeViewContent);
                this.content = editor.getHTML();
                this.$dispatch('content-change', this.content);
            }
            this.codeViewOpen = false;
        },

        highlightedCodeView() {
            return highlightHtml(this.codeViewContent);
        },

        codeViewLines() {
            return this.codeViewContent.split('\n').length;
        },

        formatCode() {
            this.codeViewContent = formatHtmlCode(this.codeViewContent);
        },

        async copyCode() {
            try {
                // navigator.clipboard só existe em contexto seguro (https/
                // localhost) — em dev local (http://intranet.test) ela nem
                // existe no objeto navigator, por isso o fallback abaixo com
                // um textarea temporário + execCommand("copy"), que funciona
                // em http também (API mais antiga, mas ainda suportada).
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(this.codeViewContent);
                } else {
                    const temp = document.createElement('textarea');
                    temp.value = this.codeViewContent;
                    temp.style.position = 'fixed';
                    temp.style.opacity = '0';
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                }
                this.codeCopied = true;
                setTimeout(() => { this.codeCopied = false; }, 1500);
            } catch {
                // Mesmo com o fallback, copiar pode falhar (ex.: aba sem
                // foco) — nesse caso só não mostra a confirmação.
            }
        },
        codeCopied: false,

        fullscreen: false,
        toggleFullscreen() {
            this.fullscreen = !this.fullscreen;
        },
    };
};

window.dragDrop = (wire, method) => ({
    draggedId: null,
    armedId: null,
    saving: false,
    arm(event) {
        const item = event.target.closest('[data-sort-id]');
        if (!item) return;
        this.armedId = item.dataset.sortId;
        item.draggable = true;
    },
    disarm(event) {
        const item = event.target.closest('[data-sort-id]');
        if (item && this.draggedId === null) item.draggable = false;
        if (this.draggedId === null) this.armedId = null;
    },
    start(event) {
        const item = event.target.closest('[data-sort-id]');
        if (!item || this.armedId !== item.dataset.sortId) {
            event.preventDefault();
            return;
        }
        this.draggedId = item.dataset.sortId;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', this.draggedId);
        requestAnimationFrame(() => item.classList.add('opacity-50', 'ring-2', 'ring-emerald-500'));
    },
    end(event) {
        const item = event.target.closest('[data-sort-id]');
        item?.classList.remove('opacity-50', 'ring-2', 'ring-emerald-500');
        if (item) item.draggable = false;
        this.draggedId = null;
        this.armedId = null;
    },
    async drop(event) {
        event.preventDefault();
        event.stopPropagation();
        const target = event.target.closest('[data-sort-id]');
        const source = [...this.$root.querySelectorAll('[data-sort-id]')].find(item => item.dataset.sortId === this.draggedId);
        if (!source || !target || source === target) return;
        const bounds = target.getBoundingClientRect();
        const horizontal = this.$root.scrollWidth > this.$root.clientWidth || Math.abs(source.offsetTop - target.offsetTop) < Math.max(source.offsetHeight, target.offsetHeight);
        const after = horizontal
            ? event.clientX > bounds.left + bounds.width / 2
            : event.clientY > bounds.top + bounds.height / 2;
        target.parentNode.insertBefore(source, after ? target.nextSibling : target);
        const ids = [...this.$root.querySelectorAll('[data-sort-id]')].map(item => Number(item.dataset.sortId));
        this.saving = true;
        try {
            await wire.call(method, ids);
        } finally {
            this.saving = false;
            source.classList.remove('opacity-50', 'ring-2', 'ring-emerald-500');
            source.draggable = false;
            this.draggedId = null;
            this.armedId = null;
        }
    },
});
