import { Summary } from '../types';

export interface Slide {
    title: string;
    content: string[];
}

export class SlideService {
    public static generateSlides(summary: Summary): Slide[] {
        const slides: Slide[] = [];
        
        // Example logic to convert summary into slides
        const sections = summary.text.split('\n\n'); // Split by paragraphs
        sections.forEach((section, index) => {
            const slide: Slide = {
                title: `Slide ${index + 1}`,
                content: section.split('\n').filter(line => line.trim() !== ''),
            };
            slides.push(slide);
        });

        return slides;
    }
}