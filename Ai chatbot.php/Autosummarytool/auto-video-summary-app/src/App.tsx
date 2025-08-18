import React from 'react';
import { BrowserRouter as Router, Route, Switch } from 'react-router-dom';
import Home from './pages/Home';
import Summary from './pages/Summary';

const App: React.FC = () => {
  return (
    <Router>
      <Switch>
        <Route path="/" exact component={Home} />
        <Route path="/summary" component={Summary} />
      </Switch>
    </Router>
  );
};

export default App;