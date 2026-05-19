import axios from 'axios';

const WHISPER_API_URL = 'https://api.whisper.ai/transcribe';
const OPENAI_API_URL = 'https://api.openai.com/v1/engines/davinci-codex/completions';
const OPENAI_API_KEY = 'your_openai_api_key_here';

export const transcribeVideo = async (videoFile: File): Promise<string> => {
    const formData = new FormData();
    formData.append('file', videoFile);

    const response = await axios.post(WHISPER_API_URL, formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });

    return response.data.transcription;
};

export const generateSummary = async (text: string): Promise<string> => {
    const response = await axios.post(OPENAI_API_URL, {
        prompt: text,
        max_tokens: 150,
        temperature: 0.7,
    }, {
        headers: {
            'Authorization': `Bearer ${OPENAI_API_KEY}`,
            'Content-Type': 'application/json',
        },
    });

    return response.data.choices[0].text.trim();
};

export const createSlides = (summary: string): Array<{ title: string; points: string[] }> => {
    const sections = summary.split('\n\n');
    return sections.map((section, index) => {
        const lines = section.split('\n');
        return {
            title: lines[0],
            points: lines.slice(1).map(line => line.trim()).filter(line => line.length > 0),
        };
    });
};