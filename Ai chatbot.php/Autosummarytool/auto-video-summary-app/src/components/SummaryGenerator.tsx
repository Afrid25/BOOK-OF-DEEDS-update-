import React, { useState } from 'react';
import { aiSummaryService } from '../services/aiSummaryService';
import './SummaryGenerator.css';

const SummaryGenerator = () => {
    const [file, setFile] = useState(null);
    const [summary, setSummary] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleFileChange = (event) => {
        setFile(event.target.files[0]);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        if (!file) {
            setError('Please upload a file.');
            return;
        }
        setLoading(true);
        setError('');

        try {
            const generatedSummary = await aiSummaryService.generateSummary(file);
            setSummary(generatedSummary);
        } catch (err) {
            setError('Error generating summary. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="summary-generator">
            <form onSubmit={handleSubmit}>
                <input type="file" accept=".mp4,.txt,.pdf,.docx" onChange={handleFileChange} />
                <button type="submit" disabled={loading}>
                    {loading ? 'Generating...' : 'Generate Summary'}
                </button>
            </form>
            {error && <div className="error">{error}</div>}
            {summary && (
                <div className="summary-card">
                    <h2>Generated Summary</h2>
                    <p>{summary}</p>
                </div>
            )}
        </div>
    );
};

export default SummaryGenerator;