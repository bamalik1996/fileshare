@extends('layouts.app')

@section('title', 'Home Page')

@section('content')
    <style>
        .form-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-top: 40px;
        }

        .title {
            color: #333;
        }

        .input, .textarea {
            margin-bottom: 20px;
        }

        .cta-button {
            background-color: #00AEEF;
            color: white;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 6px;
            text-align: center;
            display: inline-block;
        }

        .cta-button:hover {
            background-color: #007BB5;
        }

        .highlight {
            font-weight: bold;
            color: #ff6f00;
        }

    </style>


        <h1 class="title is-2 has-text-centered has-text-primary">Share Your Experience</h1>
        
        <div class="columns is-centered">
            <div class="column is-half">
                <div class="form-box">
                    <h3 class="title is-3 has-text-centered">We Value Your Feedback</h3>
                    
                    <!-- Email Input -->
                    <div class="field">
                        <label class="label" for="email">Email</label>
                        <div class="control">
                            <input class="input" type="email" id="email" placeholder="Enter your email address" required>
                        </div>
                    </div>

                    <!-- Feedback Textarea -->
                    <div class="field">
                        <label class="label" for="feedback">Feedback</label>
                        <div class="control">
                            <textarea class="textarea" id="feedback" placeholder="Type your feedback here" required></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="has-text-centered">
                        <button class="cta-button" type="submit" onclick="submitFeedback()">Send Feedback</button>
                    </div>
                </div>
            </div>
        </div>

<script>
    function submitFeedback() {
        const email = document.getElementById("email").value;
        const feedback = document.getElementById("feedback").value;
        
        if (email && feedback) {
            alert("Thank you for your feedback!");
            // You can process the feedback data here (e.g., send it to your server)
        } else {
            alert("Please fill in both fields.");
        }
    }
</script>

@endsection
