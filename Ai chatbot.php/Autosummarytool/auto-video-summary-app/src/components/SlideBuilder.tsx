import React from 'react';
import SlideSection from './SlideSection';

interface SlideBuilderProps {
    summary: string;
}

const SlideBuilder: React.FC<SlideBuilderProps> = ({ summary }) => {
    const slides = summary.split('\n').map((section, index) => {
        const [title, ...points] = section.split('. ');
        return { title, points };
    });

    return (
        <div>
            {slides.map((slide, index) => (
                <SlideSection key={index} title={slide.title} points={slide.points} />
            ))}
        </div>
    );
};

export default SlideBuilder;