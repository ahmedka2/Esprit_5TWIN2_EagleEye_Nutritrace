/**
 * NutriTrace AI — Module d'IA Embarquée Hybride (ONNX Runtime Web / @huggingface/transformers + Backend NutriTraceAiService)
 *
 * Modèles pré-entraînés officiels :
 * 1. Xenova/vit-base-patch16-224 (Vision Transformer — Google Research / Google Brain)
 * 2. Xenova/multilingual-e5-small (Multilingual Embeddings 384d — Microsoft Research & UKP Lab)
 * 3. Xenova/distilbert-base-multilingual-cased-sentiments-student (DistilBERT Sentiment — Google AI & Hugging Face Research)
 */

export const MODEL_VISION = 'Xenova/vit-base-patch16-224';
export const MODEL_EMBEDDINGS = 'Xenova/multilingual-e5-small';
export const MODEL_SENTIMENT = 'Xenova/distilbert-base-multilingual-cased-sentiments-student';

export const NUTRITRACE_AI_MODELS = {
    MODEL_VISION,
    MODEL_EMBEDDINGS,
    MODEL_SENTIMENT,
    origins: {
        [MODEL_VISION]: 'Google Research (Google Brain) / Hugging Face ONNX',
        [MODEL_EMBEDDINGS]: 'Microsoft Research & UKP Lab / Hugging Face ONNX',
        [MODEL_SENTIMENT]: 'Google AI (mBERT) & Hugging Face Research / ONNX',
    },
};

class NutriTraceAiClient {
    constructor() {
        this.MODEL_VISION = MODEL_VISION;
        this.MODEL_EMBEDDINGS = MODEL_EMBEDDINGS;
        this.MODEL_SENTIMENT = MODEL_SENTIMENT;
        this.pipelines = {};
    }

    /**
     * Charge à la demande un pipeline ONNX via @huggingface/transformers (si activé dans le navigateur)
     */
    async loadOnnxPipeline(task, modelName) {
        const key = `${task}:${modelName}`;
        if (this.pipelines[key]) {
            return this.pipelines[key];
        }
        try {
            const { pipeline } = await import('https://cdn.jsdelivr.net/npm/@huggingface/transformers@3.0.0');
            this.pipelines[key] = await pipeline(task, modelName);
            return this.pipelines[key];
        } catch (err) {
            return null;
        }
    }

    /**
     * Analyse d'un signalement qualité (Xenova/multilingual-e5-small + Xenova/vit-base-patch16-224)
     */
    async analyzeSignalement({ description, photoFile = null, csrfToken = '' }) {
        let visionLabel = null;

        if (photoFile) {
            try {
                const visionPipe = await Promise.race([
                    this.loadOnnxPipeline('image-classification', this.MODEL_VISION),
                    new Promise((resolve) => setTimeout(() => resolve(null), 1200)),
                ]);
                if (visionPipe) {
                    const objectUrl = URL.createObjectURL(photoFile);
                    const out = await visionPipe(objectUrl, { topk: 1 });
                    URL.revokeObjectURL(objectUrl);
                    if (Array.isArray(out) && out[0]?.label) {
                        visionLabel = `${out[0].label} (${Math.round((out[0].score || 0.85) * 100)}% — ViT-224)`;
                    }
                }
            } catch (_) {
                visionLabel = null;
            }
        }

        const response = await fetch('/ai/analyze-signalement', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                description,
                vision_label: visionLabel,
                photo_filename: photoFile ? photoFile.name : null,
            }),
        });

        return response.json();
    }

    /**
     * Suggestion de réponses rapides par Similarité Cosinus (Xenova/multilingual-e5-small)
     */
    async suggestReplies({ message, csrfToken = '' }) {
        const response = await fetch('/ai/suggest-replies', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ message }),
        });

        return response.json();
    }

    /**
     * Prédiction de la note 1-5 étoiles sur un favori (Xenova/distilbert-base-multilingual-cased-sentiments-student)
     */
    async analyzeNote({ note, csrfToken = '' }) {
        const response = await fetch('/ai/analyze-note', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ note }),
        });

        return response.json();
    }
}

if (typeof window !== 'undefined') {
    window.NutriTraceAI = new NutriTraceAiClient();
}

export default NutriTraceAiClient;
