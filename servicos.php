<style>
  .grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
  }

  @media (max-width: 768px) {
    .grid-container {
      grid-template-columns: 1fr;
    }
  }

  @media (min-width: 769px) and (max-width: 1024px) {
    .grid-container {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (min-width: 1025px) {
    .grid-container {
      grid-template-columns: repeat(3, 1fr);
    }
  }
</style>

<div class="grid-container">
  <div class="grid-item"><img src="image1.jpg" alt="Image 1"></div>
  <div class="grid-item"><img src="image2.jpg" alt="Image 2"></div>
  <div class="grid-item"><img src="image3.jpg" alt="Image 3"></div>
  <div class="grid-item"><img src="image4.jpg" alt="Image 4"></div>
  <div class="grid-item"><img src="image5.jpg" alt="Image 5"></div>
</div>