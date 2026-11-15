const ESTILO = `
    :host { display: block; text-align: right; font-size: .875rem; color: var(--bs-secondary-color, #6c757d); }
    .perto { color: var(--bs-warning-text-emphasis, #997404); }
    .limite { color: var(--bs-danger-text-emphasis, #b02a37); font-weight: 600; }
`;

class LimiteCaracteres extends HTMLElement {
    static observedAttributes = ['for', 'max'];

    private campo: HTMLInputElement | null = null;
    private readonly contador: HTMLSpanElement;

    constructor() {
        super();
        const raiz = this.attachShadow({ mode: 'open' });
        raiz.innerHTML = `<style>${ESTILO}</style><span></span>`;
        this.contador = raiz.querySelector('span')!;
    }

    connectedCallback(): void {
        this.conectarCampo();
    }

    disconnectedCallback(): void {
        this.campo?.removeEventListener('input', this.atualizar);
        this.campo = null;
    }

    attributeChangedCallback(): void {
        if (this.isConnected) {
            this.conectarCampo();
        }
    }

    private conectarCampo(): void {
        this.campo?.removeEventListener('input', this.atualizar);
        this.campo = document.getElementById(this.getAttribute('for') ?? '') as HTMLInputElement | null;
        this.campo?.addEventListener('input', this.atualizar);
        this.atualizar();
    }

    private readonly atualizar = (): void => {
        const max = Number(this.getAttribute('max'));
        const total = this.campo?.value.length ?? 0;

        this.contador.textContent = this.campo ? `${total}/${max}` : '';
        this.contador.className = total >= max ? 'limite' : total >= max * 0.8 ? 'perto' : '';
    };
}

customElements.define('limite-caracteres', LimiteCaracteres);
