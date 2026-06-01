// Mobile Menu Toggle
        const mobileMenu = document.querySelector('.mobile-menu');
        const navMenu = document.querySelector('nav ul');
        
        mobileMenu.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });

        // Close mobile menu when clicking links
        document.querySelectorAll('nav ul li a').forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
                mobileMenu.querySelector('i').classList.remove('fa-times');
                mobileMenu.querySelector('i').classList.add('fa-bars');
            });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Animate stats when they come into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if(entry.isIntersecting) {
                    const statNumber = entry.target.querySelector('.stat-number');
                    if(statNumber) {
                        const text = statNumber.textContent;
                        const finalNumber = parseInt(text.replace('+', ''));
                        statNumber.textContent = '0';
                        
                        let current = 0;
                        const increment = finalNumber / 50;
                        const timer = setInterval(() => {
                            current += increment;
                            if(current >= finalNumber) {
                                statNumber.textContent = text;
                                clearInterval(timer);
                            } else {
                                statNumber.textContent = Math.floor(current);
                            }
                        }, 30);
                    }
                }
            });
        }, { threshold: 0.5 });

        // Observe all stat items
        document.querySelectorAll('.stat-item').forEach(item => {
            observer.observe(item);
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if(window.scrollY > 100) {
                header.style.boxShadow = '0 10px 30px rgba(198, 40, 40, 0.2)';
                header.style.transform = 'translateY(-5px)';
                header.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                header.style.boxShadow = '0 2px 20px rgba(198, 40, 40, 0.1)';
                header.style.transform = 'translateY(0)';
                header.style.background = 'white';
            }
        });

        // Testimonial rotation
        const testimonials = [
            {
                quote: "Through Urban Ministry's VSLA program, I saved enough to start my tailoring business. Today, I employ three other women and can send all my children to school.",
                author: "Amina Yusuf",
                role: "VSLA Beneficiary, Jos North"
            },
            {
                quote: "The trauma healing sessions helped me recover from the loss of my family. Now I'm a peace advocate in my community, helping others find healing.",
                author: "James Kure",
                role: "Trauma Healing Participant, Barkin Ladi"
            },
            {
                quote: "Urban Ministry's literacy classes changed my life at 45. I can now read the Bible, help my children with homework, and manage my small business accounts.",
                author: "Grace Sunday",
                role: "Adult Literacy Student, Jos South"
            }
        ];

        let currentTestimonial = 0;
        const testimonialElement = document.querySelector('.testimonial');

        function rotateTestimonial() {
            if (testimonialElement) {
                testimonialElement.style.opacity = '0';
                testimonialElement.style.transform = 'translateX(100px)';
                
                setTimeout(() => {
                    currentTestimonial = (currentTestimonial + 1) % testimonials.length;
                    const testimonial = testimonials[currentTestimonial];
                    
                    testimonialElement.innerHTML = `
                        <i class="fas fa-quote-left"></i>
                        <p>"${testimonial.quote}"</p>
                        <div class="testimonial-author">${testimonial.author}</div>
                        <div class="testimonial-role">${testimonial.role}</div>
                    `;
                    
                    testimonialElement.style.opacity = '1';
                    testimonialElement.style.transform = 'translateX(0)';
                }, 300);
            }
        }

        // Rotate testimonial every 8 seconds
        setInterval(rotateTestimonial, 8000);

        // Logo fallback
        document.addEventListener('DOMContentLoaded', function() {
            const logoImg = document.querySelector('.logo-image');
            const logoPlaceholder = document.querySelector('.logo-placeholder');
            
            if (logoImg && logoImg.complete && logoImg.naturalHeight === 0) {
                logoImg.style.display = 'none';
                if (logoPlaceholder) logoPlaceholder.style.display = 'flex';
            }
            
            logoImg.addEventListener('error', function() {
                this.style.display = 'none';
                if (logoPlaceholder) logoPlaceholder.style.display = 'flex';
            });
        });