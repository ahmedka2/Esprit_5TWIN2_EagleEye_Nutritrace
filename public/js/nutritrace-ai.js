/**
 * NutriTrace AI — Module d'IA Embarquée Hybride (ONNX Runtime Web / @huggingface/transformers + Backend NutriTraceAiService)
 *
 * Modèles pré-entraînés officiels :
 * 1. Xenova/vit-base-patch16-224 (Vision Transformer — Google Research / Google Brain)
 * 2. Xenova/multilingual-e5-small (Multilingual Embeddings 384d — Microsoft Research & UKP Lab)
 * 3. Xenova/distilbert-base-multilingual-cased-sentiments-student (DistilBERT Sentiment — Google AI & Hugging Face Research)
 */
(function (global) {
    'use strict';

    const MODEL_VISION = 'Xenova/vit-base-patch16-224';
    const MODEL_EMBEDDINGS = 'Xenova/multilingual-e5-small';
    const MODEL_SENTIMENT = 'Xenova/distilbert-base-multilingual-cased-sentiments-student';

    class NutriTraceAiClient {
        constructor() {
            this.MODEL_VISION = MODEL_VISION;
            this.MODEL_EMBEDDINGS = MODEL_EMBEDDINGS;
            this.MODEL_SENTIMENT = MODEL_SENTIMENT;
            this.pipelines = {};
        }

        async analyzeSignalement({ description, photoFile = null, csrfToken = '' }) {
            const response = await fetch('/ai/analyze-signalement', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    description: description,
                    photo_filename: photoFile ? photoFile.name : null,
                }),
            });

            return response.json();
        }

        async suggestReplies({ message, csrfToken = '' }) {
            const response = await fetch('/ai/suggest-replies', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ message: message }),
            });

            return response.json();
        }

        async analyzeNote({ note, csrfToken = '' }) {
            const response = await fetch('/ai/analyze-note', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ note: note }),
            });

            return response.json();
        }
    }

    global.NutriTraceAI = new NutriTraceAiClient();
})(window);
