import React from 'react';

interface SlideSectionProps {
    title: string;
    bulletPoints: string[];
}

const SlideSection: React.FC<SlideSectionProps> = ({ title, bulletPoints }) => {
    return (
        <div className="slide-section">
            <h2>{title}</h2>
            <ul>
                {bulletPoints.map((point, index) => (
                    <li key={index}>{point}</li>
                ))}
            </ul>
        </div>
    );
};

export default SlideSection;