/**
 * Advertisement Slider
 * Handles sliding ads in the fixed footer
 */
document.addEventListener('DOMContentLoaded', function() {
    // Ad slider functionality
    const adSlider = document.querySelector('.ad-slider');
    const adSlides = document.querySelectorAll('.ad-slide');
    
    if (!adSlider || adSlides.length === 0) return;
    
    let currentSlide = 0;
    let slideInterval;
    
    // Handle missing images by creating fallback content
    adSlides.forEach((slide, index) => {
        const img = slide.querySelector('img');
        if (!img || !img.getAttribute('src') || img.getAttribute('src').includes('undefined')) {
            // Create a fallback colored background with text
            slide.innerHTML = '';
            slide.style.backgroundColor = index === 0 ? '#3498db' : (index === 1 ? '#e74c3c' : '#27ae60');
            slide.style.color = '#ffffff';
            slide.style.textAlign = 'center';
            slide.style.padding = '20px';
            slide.style.fontSize = '20px';
            slide.style.fontWeight = '600';
            
            const textContent = document.createElement('div');
            textContent.innerHTML = `
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%;">
                    <div style="margin-bottom:8px;">SPECIAL OFFER</div>
                    <div style="font-size:24px; margin-bottom:8px;">Zero9communication.com</div>
                    <div style="font-size:14px;">Click to visit our website</div>
                </div>
            `;
            slide.appendChild(textContent);
        }
    });
    
    // Function to move to a specific slide
    function goToSlide(index) {
        if (index < 0) {
            index = adSlides.length - 1;
        } else if (index >= adSlides.length) {
            index = 0;
        }
        
        adSlider.style.transform = `translateX(-${index * 100}%)`;
        currentSlide = index;
    }
    
    // Next slide function
    function nextSlide() {
        goToSlide(currentSlide + 1);
    }
    
    // Start auto sliding
    function startSlideShow() {
        slideInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
    }
    
    // Event listeners for slides
    adSlides.forEach(slide => {
        slide.addEventListener('click', () => {
            // Open link when ad is clicked
            window.open('https://zero9communication.com/', '_blank');
        });
    });
    
    // Initialize slider
    goToSlide(0);
    startSlideShow();
}); 