import React from 'react';
import { useLocation } from 'react-router-dom';
import SummaryGenerator from '../components/SummaryGenerator';
import SlideBuilder from '../components/SlideBuilder';

const Summary = () => {
    const location = useLocation();
    const { summaryData } = location.state || {};

    return (
        <div>
            <h1>AI Generated Summary</h1>
            {summaryData ? (
                <>
                    <SummaryGenerator summary={summaryData} />
                    <SlideBuilder summary={summaryData} />
                </>
            ) : (
                <p>No summary data available. Please upload a video or text note to generate a summary.</p>
            )}
        </div>
    );
};

export default Summary;